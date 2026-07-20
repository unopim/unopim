<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;

class CategoryTree implements PimTool
{
    public function __construct(
        protected EmbeddingSimilarityService $embeddingSimilarityService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $embeddingSimilarityService = $this->embeddingSimilarityService;

        return new class($context, $embeddingSimilarityService) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(
                ChatContext $context,
                protected EmbeddingSimilarityService $embeddingSimilarityService,
            ) {
                parent::__construct($context);
            }

            /**
             * Default number of levels expanded per call.
             */
            private const int DEFAULT_DEPTH = 2;

            /**
             * Maximum number of levels expanded per call.
             */
            private const int MAX_DEPTH = 5;

            /**
             * Default number of children returned per node per level.
             */
            private const int DEFAULT_CHILDREN_PER_LEVEL = 20;

            /**
             * Maximum number of children returned per node per level.
             */
            private const int MAX_CHILDREN_PER_LEVEL = 100;

            /**
             * Hard cap on total nodes returned in a single call.
             */
            private const int MAX_NODES = 500;

            public function name(): string
            {
                return 'category_tree';
            }

            public function description(): string
            {
                return 'Explore the category tree hierarchically with lazy drill-down. '
                    .'Call without parent_code to get top-level categories; pass parent_code to expand a specific branch. '
                    .'Each returned node includes total_children and has_more: when has_more is true, some of that node\'s '
                    .'descendants were not returned (cut off by depth or children_per_level), so call this tool again with '
                    .'that node\'s code as parent_code to drill deeper. Use depth (default 2) to control how many levels '
                    .'are expanded and children_per_level (default 20) to control how many children are listed per node. '
                    .'Prefer several narrow drill-down calls over one huge call on large catalogs.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'parent_code'        => $schema->string()->description('Category code to drill into. Omit to list top-level categories.'),
                    'depth'              => $schema->integer()->description('Number of levels to expand below the starting point (default 2, max 5).'),
                    'children_per_level' => $schema->integer()->description('Maximum children returned per node at each level (default 20, max 100).'),
                    'relevance_query'    => $schema->string()->description('Optional product/topic text. When given, the listed branches are semantically ranked against it so the most relevant branches are expanded first.'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.categories')) {
                    return $denied;
                }

                $depth = $request->integer('depth', self::DEFAULT_DEPTH);
                $depth = min(max($depth, 1), self::MAX_DEPTH);

                $perLevel = $request->integer('children_per_level', self::DEFAULT_CHILDREN_PER_LEVEL);
                $perLevel = min(max($perLevel, 1), self::MAX_CHILDREN_PER_LEVEL);

                $parentCode = $request->string('parent_code')->toString() ?: null;

                $parent = null;

                if ($parentCode !== null) {
                    $parent = DB::table('categories')
                        ->select('id', 'code', 'parent_id', 'additional_data')
                        ->where('code', $parentCode)
                        ->first();

                    if (! $parent) {
                        return json_encode([
                            'error' => "Category with code '{$parentCode}' not found. Use list_categories to search for valid codes.",
                        ]);
                    }
                }

                $relevanceQuery = trim($request->string('relevance_query')->toString());

                // With a relevance query, over-fetch the first level and keep
                // the semantically closest branches so drill-down expands the
                // winners instead of the first N by tree position.
                $fetchLimit = $relevanceQuery !== '' ? self::MAX_CHILDREN_PER_LEVEL : $perLevel;

                [$rows, $totalAtLevel] = $this->fetchFirstLevel($parent?->id, $fetchLimit);

                if ($relevanceQuery !== '' && $rows->count() > 1) {
                    $rows = $this->pruneByRelevance($rows, $relevanceQuery, $perLevel);
                } else {
                    $rows = $rows->take($perLevel)->values();
                }

                $allRows = $rows->all();
                $frontierIds = $rows->pluck('id')->all();

                for ($level = 2; $level <= $depth && $frontierIds !== [] && count($allRows) < self::MAX_NODES; $level++) {
                    $children = $this->fetchRankedChildren($frontierIds, $perLevel);

                    $allRows = array_merge($allRows, $children->all());
                    $frontierIds = $children->pluck('id')->all();
                }

                $allRows = array_slice($allRows, 0, self::MAX_NODES);

                $tree = $this->assembleTree($allRows);

                return json_encode([
                    'parent'             => $parent ? $this->presentCategory($parent) : null,
                    'depth'              => $depth,
                    'children_per_level' => $perLevel,
                    'total_at_level'     => $totalAtLevel,
                    'has_more_at_level'  => $totalAtLevel > count($tree),
                    'returned'           => count($allRows),
                    'categories'         => $tree,
                ]);
            }

            /**
             * Fetch the first visible level: children of the given parent, or top-level categories.
             *
             * @return array{0: Collection, 1: int}
             */
            private function fetchFirstLevel(?int $parentId, int $perLevel): array
            {
                $query = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data');

                $countQuery = DB::table('categories');

                if ($parentId !== null) {
                    $query->where('parent_id', $parentId);
                    $countQuery->where('parent_id', $parentId);
                } else {
                    $query->whereNull('parent_id');
                    $countQuery->whereNull('parent_id');
                }

                return [
                    $query->orderBy('_lft')->limit($perLevel)->get(),
                    $countQuery->count(),
                ];
            }

            /**
             * Fetch up to $perLevel children for every parent id in one query,
             * using a window function so the whole level is never loaded.
             *
             * @param  array<int, int>  $parentIds
             */
            private function fetchRankedChildren(array $parentIds, int $perLevel): Collection
            {
                $ranked = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data', '_lft')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY parent_id ORDER BY _lft) as rn')
                    ->whereIn('parent_id', $parentIds);

                // Bound the level fetch itself: a wide frontier × perLevel
                // could otherwise pull thousands of rows into PHP before the
                // MAX_NODES cap applies.
                return DB::query()
                    ->fromSub($ranked, 'ranked')
                    ->where('rn', '<=', $perLevel)
                    ->orderBy('_lft')
                    ->limit(self::MAX_NODES)
                    ->get();
            }

            /**
             * Assemble flat level-ordered rows into a nested tree with per-node
             * total_children and has_more indicators.
             *
             * @param  array<int, object>  $rows
             * @return array<int, array<string, mixed>>
             */
            private function assembleTree(array $rows): array
            {
                if ($rows === []) {
                    return [];
                }

                $childCounts = DB::table('categories')
                    ->selectRaw('parent_id, COUNT(*) as total')
                    ->whereIn('parent_id', array_column($rows, 'id'))
                    ->groupBy('parent_id')
                    ->pluck('total', 'parent_id');

                $registry = [];

                foreach ($rows as $row) {
                    $registry[$row->id] = $this->presentCategory($row) + [
                        'total_children' => (int) ($childCounts[$row->id] ?? 0),
                        'children'       => [],
                    ];
                }

                $tree = [];

                foreach ($rows as $row) {
                    if ($row->parent_id !== null && isset($registry[$row->parent_id])) {
                        $registry[$row->parent_id]['children'][] = &$registry[$row->id];
                    } else {
                        $tree[] = &$registry[$row->id];
                    }
                }

                foreach ($tree as &$node) {
                    $this->markHasMore($node);
                }

                return $tree;
            }

            /**
             * Flag nodes whose descendants were cut off by depth or children_per_level.
             *
             * @param  array<string, mixed>  $node
             */
            private function markHasMore(array &$node): void
            {
                $node['has_more'] = $node['total_children'] > count($node['children']);

                foreach ($node['children'] as &$child) {
                    $this->markHasMore($child);
                }
            }

            /**
             * Map a category row to its locale-aware public shape.
             *
             * @return array{id: int, code: string, name: string, parent_id: int|null}
             */
            /**
             * Keep the branches semantically closest to the relevance query,
             * in relevance order. Falls back to tree order (no pruning applied
             * beyond the per-level limit) when similarity scoring is
             * unavailable — the service returns [] on any failure.
             */
            private function pruneByRelevance(Collection $rows, string $relevanceQuery, int $perLevel): Collection
            {
                $documents = $rows
                    ->map(fn (object $row): string => $row->code.' | '.$this->presentCategory($row)['name'])
                    ->values()
                    ->all();

                $ranked = $this->embeddingSimilarityService->rank($relevanceQuery, $documents, $perLevel);

                if ($ranked === []) {
                    return $rows->take($perLevel)->values();
                }

                $rows = $rows->values();
                $pruned = collect();

                foreach ($ranked as $item) {
                    if (isset($rows[$item['index']])) {
                        $pruned->push($rows[$item['index']]);
                    }
                }

                return $pruned->take($perLevel)->values();
            }

            private function presentCategory(object $category): array
            {
                $data = json_decode($category->additional_data ?? '', true) ?? [];

                $name = $data['locale_specific'][$this->context->locale]['name']
                    ?? $data['locale_specific'][config('app.fallback_locale', 'en_US')]['name']
                    ?? $category->code;

                return [
                    'id'        => $category->id,
                    'code'      => $category->code,
                    'name'      => $name,
                    'parent_id' => $category->parent_id,
                ];
            }
        };
    }
}
