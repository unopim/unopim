<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\SemanticRankingService;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class SearchProducts implements PimTool
{
    public function __construct(
        protected SemanticRankingService $semanticRankingService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $semanticRankingService = $this->semanticRankingService;

        return new class($context, $semanticRankingService) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(
                ChatContext $context,
                protected SemanticRankingService $semanticRankingService,
            ) {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'search_products';
            }

            public function description(): string
            {
                return 'Search products by SKU, name, or status.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'query'  => $schema->string()->description('Search term: SKU pattern, product name keyword, or leave empty for all'),
                    'status' => $schema->string()->enum(['active', 'inactive', 'all'])->description('Filter by product status'),
                    'limit'  => $schema->integer()->description('Maximum results to return (default 10, max 50)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                $query = $request->string('query')->toString() ?: null;
                $status = $request->string('status')->toString() ?: 'all';
                $limit = $request->has('limit') ? (int) $request->get('limit') : 10;

                $limit = min(max($limit, 1), 50);
                $candidateLimit = min(max($limit * 5, $limit), 200);

                // Laravel prefixes the alias (e.g. `p` → `wk_p`), but table
                // prefixes are not applied inside DB::raw(). Build the raw
                // alias explicitly so JSON selects resolve to the same alias
                // Laravel generates for the FROM clause.
                $prefix = DB::getTablePrefix();
                $grammar = GrammarQueryManager::getGrammar();

                $qb = DB::table('products as p')
                    ->leftJoin('attribute_families as af', 'af.id', '=', 'p.attribute_family_id')
                    ->select(
                        'p.id', 'p.sku', 'p.type', 'p.status', 'af.code as family_code',
                        DB::raw($grammar->jsonExtract("{$prefix}p.values", 'channel_locale_specific', $this->context->channel, $this->context->locale, 'name').' as product_name'),
                        DB::raw($grammar->jsonExtract("{$prefix}p.values", 'common', 'url_key').' as url_key'),
                    );

                if ($query) {
                    $escaped = str_replace(['%', '_'], ['\%', '\_'], $query);
                    $context = $this->context;
                    $qb->where(function (Builder $q) use ($escaped, $context, $prefix, $grammar) {
                        $q->where('p.sku', 'like', "%{$escaped}%")
                            ->orWhere('p.values->common->url_key', 'like', "%{$escaped}%")
                            ->orWhereRaw($grammar->jsonExtract("{$prefix}p.values", 'channel_locale_specific', $context->channel, $context->locale, 'name').' LIKE ?', ["%{$escaped}%"]);
                    });
                }

                if ($status !== 'all') {
                    $qb->where('p.status', $status === 'active' ? 1 : 0);
                }

                $products = $qb->orderByDesc('p.id')->limit($candidateLimit)->get();

                $editBaseUrl = route('admin.catalog.products.edit', ['id' => '__ID__']);

                $results = $products->map(fn (\stdClass $p) => [
                    'id'              => $p->id,
                    'sku'             => $p->sku,
                    'name'            => $p->product_name ?? $p->url_key ?? '(unnamed)',
                    'type'            => $p->type,
                    'status'          => $p->status ? 'active' : 'inactive',
                    'family'          => $p->family_code,
                    'edit_url'        => str_replace('__ID__', (string) $p->id, $editBaseUrl),
                    'relevance_score' => null,
                ]);

                $hasSemanticQuery = ! empty($query) && mb_strlen(trim($query)) > 2;

                if ($hasSemanticQuery && $results->count() > 2) {
                    $documents = $results
                        ->map(fn (array $item) => implode(' | ', [
                            $item['sku'],
                            $item['name'],
                            $item['type'],
                            (string) $item['family'],
                            $item['status'],
                        ]))
                        ->values()
                        ->toArray();

                    $ranked = $this->semanticRankingService->rank($query, $documents, $limit);

                    if ($ranked !== []) {
                        $reranked = collect();

                        foreach ($ranked as $item) {
                            $index = $item['index'];

                            if (! isset($results[$index])) {
                                continue;
                            }

                            $row = $results[$index];
                            $row['relevance_score'] = $item['score'];
                            $reranked->push($row);
                        }

                        $results = $reranked->take($limit)->values();
                    } else {
                        $results = $results->take($limit)->values();
                    }
                } else {
                    $results = $results->take($limit)->values();
                }

                return json_encode([
                    'total'    => $results->count(),
                    'products' => $results->toArray(),
                ]);
            }
        };
    }
}
