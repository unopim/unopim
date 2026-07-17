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
                return 'Search products by SKU, name, status, or any attribute value (EAN, brand, model number, etc.). Searches the whole catalog by default; pass family_code and/or category_code to narrow the search to one attribute family or category.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'query'         => $schema->string()->description('Search term: SKU pattern, product name keyword, any attribute value (e.g. EAN), or leave empty for all'),
                    'status'        => $schema->string()->enum(['active', 'inactive', 'all'])->description('Filter by product status'),
                    'family_code'   => $schema->string()->description('Optional attribute family code to restrict results to (e.g. the current product family). Omit for a catalog-wide search.'),
                    'category_code' => $schema->string()->description('Optional category code to restrict results to products assigned to that category. Omit for a catalog-wide search.'),
                    'limit'         => $schema->integer()->description('Maximum results to return (default 10, max 50)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                $query = $request->string('query')->toString() ?: null;
                $status = $request->string('status')->toString() ?: 'all';
                $familyCode = $request->string('family_code')->toString() ?: null;
                $categoryCode = $request->string('category_code')->toString() ?: null;
                $limit = $request->integer('limit', 10);

                // Codes participate in SQL (bound) and JSON semantics — reject
                // anything outside the safe code alphabet up front.
                foreach (['family_code' => $familyCode, 'category_code' => $categoryCode] as $field => $code) {
                    if ($code !== null && ! preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
                        return json_encode(['error' => "Invalid {$field}: only letters, numbers, underscores and hyphens are allowed."]);
                    }
                }

                $limit = min(max($limit, 1), 50);
                $candidateLimit = min(max($limit * 5, $limit), 200);

                // Laravel prefixes the alias (e.g. `p` → `wk_p`), but table
                // prefixes are not applied inside DB::raw(). Build the raw
                // alias explicitly so JSON selects resolve to the same alias
                // Laravel generates for the FROM clause.
                $prefix = DB::getTablePrefix();
                $grammar = GrammarQueryManager::getGrammar();
                $searchable = $this->searchableAttributes();

                $nameAttribute = $searchable->firstWhere('code', 'name');
                $namePath = $nameAttribute
                    ? $this->valuePath($nameAttribute)
                    : ['channel_locale_specific', $this->context->channel, $this->context->locale, 'name'];

                $qb = DB::table('products as p')
                    ->leftJoin('attribute_families as af', 'af.id', '=', 'p.attribute_family_id')
                    ->select(
                        'p.id', 'p.sku', 'p.type', 'p.status', 'af.code as family_code',
                        DB::raw("`{$prefix}p`.`values`"),
                        DB::raw($grammar->jsonExtract("{$prefix}p.values", ...$namePath).' as product_name'),
                        DB::raw($grammar->jsonExtract("{$prefix}p.values", 'common', 'url_key').' as url_key'),
                    );

                if ($query) {
                    $escaped = str_replace(['%', '_'], ['\%', '\_'], $query);
                    $term = "%{$escaped}%";

                    $valuesAsText = DB::getDriverName() === 'pgsql'
                        ? "\"{$prefix}p\".\"values\"::text"
                        : "CAST(`{$prefix}p`.`values` AS CHAR)";

                    // The raw-text pre-filter compares against the serialized
                    // JSON, so it is only a valid superset when no character
                    // of the term can be JSON-escaped in storage (quotes,
                    // backslashes, slashes, non-ASCII). Other terms skip the
                    // pre-filter and pay the full extraction chain instead of
                    // risking false negatives.
                    $useCoarsePreFilter = (bool) preg_match('/^[a-zA-Z0-9 _.\-]+$/', $query);

                    $qb->where(function ($q) use ($term, $valuesAsText, $prefix, $grammar, $searchable, $useCoarsePreFilter) {
                        // `sku` is a structural column on the products table;
                        // every other searchable field comes from the dynamic
                        // attribute list so custom attributes are covered.
                        $q->where('p.sku', 'like', $term);

                        if ($searchable->isEmpty()) {
                            return;
                        }

                        $q->orWhere(function ($candidate) use ($term, $valuesAsText, $prefix, $grammar, $searchable, $useCoarsePreFilter) {
                            if ($useCoarsePreFilter) {
                                $candidate->whereRaw("{$valuesAsText} LIKE ?", [$term]);
                            }

                            $candidate->where(function ($attributeMatch) use ($term, $prefix, $grammar, $searchable) {
                                foreach ($searchable as $attribute) {
                                    $attributeMatch->orWhereRaw(
                                        $grammar->jsonExtract("{$prefix}p.values", ...$this->valuePath($attribute)).' LIKE ?',
                                        [$term],
                                    );
                                }
                            });
                        });
                    });
                }

                if ($status !== 'all') {
                    $qb->where('p.status', $status === 'active' ? 1 : 0);
                }

                if ($familyCode) {
                    $qb->where('af.code', $familyCode);
                }

                if ($categoryCode) {
                    // Products store assigned category codes as a JSON array
                    // under values->categories; the code is parameter-bound.
                    $qb->whereRaw(
                        $grammar->jsonContains("{$prefix}p.values", ['categories'], '?'),
                        ['"'.$categoryCode.'"'],
                    );
                }

                $products = $qb->orderByDesc('p.id')->limit($candidateLimit)->get();

                $editBaseUrl = route('admin.catalog.products.edit', ['id' => '__ID__']);

                $attributeTexts = $products->map(
                    fn ($p) => $this->flattenSearchableValues($p->values, $searchable)
                )->values();

                $results = $products->map(function ($p) use ($editBaseUrl) {
                    return [
                        'id'              => $p->id,
                        'sku'             => $p->sku,
                        'name'            => $p->product_name ?? $p->url_key ?? '(unnamed)',
                        'type'            => $p->type,
                        'status'          => $p->status ? 'active' : 'inactive',
                        'family'          => $p->family_code,
                        'edit_url'        => str_replace('__ID__', (string) $p->id, $editBaseUrl),
                        'relevance_score' => null,
                    ];
                })->values();

                $hasSemanticQuery = ! empty($query) && mb_strlen(trim($query)) > 2;

                if ($hasSemanticQuery && $results->count() > 2) {
                    $documents = $results
                        ->map(fn ($item, $index) => implode(' | ', array_filter([
                            $item['sku'],
                            $item['name'],
                            $item['type'],
                            (string) $item['family'],
                            $item['status'],
                            $attributeTexts[$index] ?? '',
                        ])))
                        ->values()
                        ->toArray();

                    $ranked = $this->semanticRankingService->rank($query, $documents, $limit);

                    if (! empty($ranked)) {
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

                $payload = [
                    'total'    => $results->count(),
                    'products' => $results->toArray(),
                ];

                if ($familyCode || $categoryCode) {
                    $payload['scope'] = array_filter([
                        'family_code'   => $familyCode,
                        'category_code' => $categoryCode,
                    ]);
                }

                return json_encode($payload);
            }

            /**
             * All textual attributes whose values should be searchable.
             * Discovered from the attributes table so custom attributes
             * (EAN, brand, etc.) are covered without any hardcoded list.
             *
             * @return Collection<int, object{code: string, value_per_locale: int, value_per_channel: int}>
             */
            protected function searchableAttributes(): Collection
            {
                return DB::table('attributes')
                    ->whereIn('type', ['text', 'textarea'])
                    ->where('code', '!=', 'sku')
                    ->get(['code', 'value_per_locale', 'value_per_channel'])
                    ->filter(fn ($attribute) => preg_match('/^[a-zA-Z0-9_]+$/', $attribute->code))
                    ->values();
            }

            /**
             * JSON path segments for an attribute's value based on its
             * locale/channel scope, mirroring Attribute::getScope().
             *
             * @return array<int, string>
             */
            protected function valuePath(object $attribute): array
            {
                return match (true) {
                    $attribute->value_per_locale && $attribute->value_per_channel => ['channel_locale_specific', $this->context->channel, $this->context->locale, $attribute->code],
                    (bool) $attribute->value_per_locale                           => ['locale_specific', $this->context->locale, $attribute->code],
                    (bool) $attribute->value_per_channel                          => ['channel_specific', $this->context->channel, $attribute->code],
                    default                                                       => ['common', $attribute->code],
                };
            }

            /**
             * Flatten a product's searchable attribute values into one string
             * so semantic reranking can score matches on any attribute.
             *
             * @param  Collection<int, object>  $searchable
             */
            protected function flattenSearchableValues(?string $json, Collection $searchable): string
            {
                if (empty($json)) {
                    return '';
                }

                $values = json_decode($json, true);

                if (! is_array($values)) {
                    return '';
                }

                $texts = [];

                foreach ($searchable as $attribute) {
                    $value = data_get($values, implode('.', $this->valuePath($attribute)));

                    if (is_string($value) && $value !== '') {
                        $texts[] = mb_substr($value, 0, 60);
                    }
                }

                return mb_substr(implode(' | ', $texts), 0, 400);
            }
        };
    }
}
