<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
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
                    .'Pass family_code to see only the branches that hold products of that attribute family. '
                    .'Prefer several narrow drill-down calls over one huge call on large catalogs.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'parent_code'        => $schema->string()->description('Category code to drill into. Omit to list top-level categories.'),
                    'depth'              => $schema->integer()->description('Number of levels to expand below the starting point (default 2, max 5).'),
                    'children_per_level' => $schema->integer()->description('Maximum children returned per node at each level (default 20, max 100).'),
                    'relevance_query'    => $schema->string()->description('Optional product/topic text. When given, the branches at every returned level are semantically ranked against it so the most relevant branches are listed and expanded first.'),
                    'family_code'        => $schema->string()->description('Optional attribute family code. When given, only categories assigned to products of that family, and their ancestors, are returned; child counts reflect the same scope.'),
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

                $familyCode = trim($request->string('family_code')->toString());

                $familyId = null;

                if ($familyCode !== '') {
                    $familyId = DB::table('attribute_families')->where('code', $familyCode)->value('id');

                    if ($familyId === null) {
                        return json_encode([
                            'error' => trans('ai-agent::app.common.import-family-not-found', ['family' => $familyCode]),
                        ]);
                    }

                    $familyId = (int) $familyId;
                }

                $relevanceQuery = trim($request->string('relevance_query')->toString());
                $ranksByRelevance = $relevanceQuery !== '';
                $candidateLimit = $ranksByRelevance ? $this->relevanceCandidateLimit($perLevel) : $perLevel;

                [$rows, $totalAtLevel] = $this->fetchFirstLevel($parent?->id, $candidateLimit, $familyId);

                if ($ranksByRelevance) {
                    $rows = $this->rankByRelevance($rows, $relevanceQuery);
                }

                $rows = $rows->take($perLevel)->values();

                $allRows = $rows->all();
                $frontierIds = $rows->pluck('id')->all();

                for ($level = 2; $level <= $depth && $frontierIds !== [] && count($allRows) < self::MAX_NODES; $level++) {
                    $children = $ranksByRelevance
                        ? $this->fetchRelevantChildren($frontierIds, $perLevel, $candidateLimit, $relevanceQuery, $familyId)
                        : $this->fetchRankedChildren($frontierIds, $perLevel, self::MAX_NODES, $familyId);

                    $allRows = array_merge($allRows, $children->all());
                    $frontierIds = $children->pluck('id')->all();
                }

                $allRows = array_slice($allRows, 0, self::MAX_NODES);

                $tree = $this->assembleTree($allRows, $familyId);

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
            private function fetchFirstLevel(?int $parentId, int $limit, ?int $familyId): array
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

                $this->scopeToFamily($query, $familyId);
                $this->scopeToFamily($countQuery, $familyId);

                return [
                    $query->orderBy('_lft')->limit($limit)->get(),
                    $countQuery->count(),
                ];
            }

            /**
             * Fetch up to $perParent children for every parent id in one query,
             * using a window function so the whole level is never loaded.
             *
             * The level fetch itself is bounded by $limit because a wide frontier
             * multiplied by $perParent could otherwise pull thousands of rows into
             * PHP before the MAX_NODES cap applies. Ordering by rank first spreads
             * that bound fairly across parents instead of exhausting it on the first ones.
             *
             * @param  array<int, int>  $parentIds
             */
            private function fetchRankedChildren(array $parentIds, int $perParent, int $limit, ?int $familyId, bool $fairShare = false): Collection
            {
                $ranked = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data', '_lft')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY parent_id ORDER BY _lft) as rn')
                    ->whereIn('parent_id', $parentIds);

                $this->scopeToFamily($ranked, $familyId);

                $query = DB::query()
                    ->fromSub($ranked, 'ranked')
                    ->where('rn', '<=', $perParent);

                if ($fairShare) {
                    $query->orderBy('rn');
                }

                return $query->orderBy('_lft')->limit($limit)->get();
            }

            /**
             * Fetch the next level ranked by relevance: every parent contributes an equal
             * share of the candidate budget, the whole level is scored at once, and each
             * parent keeps its $perLevel best children in relevance order.
             *
             * @param  array<int, int>  $parentIds
             */
            private function fetchRelevantChildren(array $parentIds, int $perLevel, int $candidateLimit, string $relevanceQuery, ?int $familyId): Collection
            {
                $perParent = max($perLevel, intdiv($candidateLimit, count($parentIds)));

                $candidates = $this->fetchRankedChildren($parentIds, $perParent, $candidateLimit, $familyId, fairShare: true);

                $kept = [];

                return $this->rankByRelevance($candidates, $relevanceQuery)
                    ->filter(function (object $row) use (&$kept, $perLevel): bool {
                        $kept[$row->parent_id] = ($kept[$row->parent_id] ?? 0) + 1;

                        return $kept[$row->parent_id] <= $perLevel;
                    })
                    ->take(self::MAX_NODES)
                    ->values();
            }

            /**
             * Order rows by semantic similarity to the relevance query, embedding them in
             * batches. Rows keep their original order when scoring is unavailable, because
             * the similarity service returns [] on any failure.
             */
            private function rankByRelevance(Collection $rows, string $relevanceQuery): Collection
            {
                $rows = $rows->values();

                if ($rows->count() < 2) {
                    return $rows;
                }

                $batchSize = max(1, (int) config('ai-agent.category_tree.relevance_batch_size', 100));
                $scores = [];

                foreach ($rows->chunk($batchSize) as $batch) {
                    $positions = $batch->keys()->all();

                    $documents = $batch
                        ->map(fn (object $row): string => $row->code.' | '.$this->presentCategory($row)['name'])
                        ->values()
                        ->all();

                    $ranked = $this->embeddingSimilarityService->rank($relevanceQuery, $documents);

                    if ($ranked === []) {
                        return $rows;
                    }

                    foreach ($ranked as $item) {
                        if (isset($positions[$item['index']])) {
                            $scores[$positions[$item['index']]] = $item['score'];
                        }
                    }
                }

                arsort($scores);

                return collect(array_keys($scores))
                    ->map(fn (int $position): object => $rows[$position])
                    ->concat($rows->diffKeys($scores)->values())
                    ->values();
            }

            /**
             * Number of candidates scored per level when ranking by relevance.
             */
            private function relevanceCandidateLimit(int $perLevel): int
            {
                return max($perLevel, (int) config('ai-agent.category_tree.relevance_candidate_limit', 1000));
            }

            /**
             * Restrict a categories query to nodes whose subtree holds a category assigned
             * to at least one product of the family, using the nested-set bounds.
             */
            private function scopeToFamily(Builder $query, ?int $familyId): void
            {
                if ($familyId === null) {
                    return;
                }

                $query->whereExists(fn (Builder $subtree): Builder => $subtree
                    ->selectRaw('1')
                    ->from('categories as scoped_categories')
                    ->whereColumn('scoped_categories._lft', '>=', 'categories._lft')
                    ->whereColumn('scoped_categories._rgt', '<=', 'categories._rgt')
                    ->whereIn('scoped_categories.code', $this->familyCategoryCodes($familyId)));
            }

            /**
             * Category codes assigned to the family's products, unnested from the
             * values->categories JSON array inside the database.
             *
             * PostgreSQL unnests with a lateral jsonb_array_elements_text() and guards
             * non-array values; MySQL and MariaDB both provide JSON_TABLE, whose column
             * takes the connection collation because MariaDB otherwise defaults it to one
             * that cannot be compared with categories.code.
             */
            private function familyCategoryCodes(int $familyId): Builder
            {
                $connection = DB::connection();
                $grammar = $connection->getQueryGrammar();
                $values = $grammar->wrap('family_products.values');
                $assigned = $grammar->wrapTable('assigned_categories');

                if ($connection->getDriverName() === 'pgsql') {
                    $unnest = "LATERAL jsonb_array_elements_text(CASE WHEN jsonb_typeof(({$values})::jsonb -> 'categories') = 'array' THEN ({$values})::jsonb -> 'categories' ELSE '[]'::jsonb END) AS {$assigned}(code)";
                } else {
                    $collation = $connection->getConfig('collation');
                    $columnType = is_string($collation) && preg_match('/^\w+$/', $collation) === 1
                        ? "VARCHAR(255) COLLATE {$collation}"
                        : 'VARCHAR(255)';

                    $unnest = "JSON_TABLE({$values}, '$.categories[*]' COLUMNS (code {$columnType} PATH '$')) AS {$assigned}";
                }

                return DB::table('products as family_products')
                    ->crossJoin(DB::raw($unnest))
                    ->where('family_products.attribute_family_id', $familyId)
                    ->select('assigned_categories.code');
            }

            /**
             * Assemble flat level-ordered rows into a nested tree with per-node
             * total_children and has_more indicators.
             *
             * @param  array<int, object>  $rows
             * @return array<int, array<string, mixed>>
             */
            private function assembleTree(array $rows, ?int $familyId): array
            {
                if ($rows === []) {
                    return [];
                }

                $countQuery = DB::table('categories')
                    ->selectRaw('parent_id, COUNT(*) as total')
                    ->whereIn('parent_id', array_column($rows, 'id'))
                    ->groupBy('parent_id');

                $this->scopeToFamily($countQuery, $familyId);

                $childCounts = $countQuery->pluck('total', 'parent_id');

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
