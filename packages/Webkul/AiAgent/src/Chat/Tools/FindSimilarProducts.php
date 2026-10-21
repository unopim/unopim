<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\AiErrorResolver;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Enums\EmbeddingRejection;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Repositories\ProductRepository;

class FindSimilarProducts implements PimTool
{
    public function __construct(
        protected EmbeddingSimilarityService $embeddingSimilarityService,
        protected ProductRepository $productRepository,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return new class($context, $this->embeddingSimilarityService, $this->productRepository) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(
                ChatContext $context,
                protected EmbeddingSimilarityService $embeddingSimilarityService,
                protected ProductRepository $productRepository,
            ) {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'find_similar_products';
            }

            public function description(): string
            {
                return 'Find similar products using AI embeddings. When a source product is known (a SKU is given, or the user is chatting from a product edit page), candidates default to the same attribute family; pass same_family_only=false to search across all families.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'query'            => $schema->string()->description('Semantic query text for similarity search'),
                    'sku'              => $schema->string()->description('Existing product SKU to find similar items for (defaults to the product currently being edited when omitted)'),
                    'same_family_only' => $schema->boolean()->description('Restrict candidates to the source product attribute family (default true). Set false to compare across all families.'),
                    'limit'            => $schema->integer()->description('Maximum similar products to return (default 10, max 30)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                $query = $request->string('query')->toString() ?: null;
                $sku = $request->string('sku')->toString() ?: null;
                $limit = $request->integer('limit', 10);

                $sameFamilyOnly = $request->boolean('same_family_only', true);

                $limit = min(max($limit, 1), 30);
                $poolLimit = 150;

                $sourceProduct = null;

                if (! empty($sku)) {
                    $sourceProduct = DB::table('products')
                        ->select('id', 'sku', 'type', 'values', 'attribute_family_id')
                        ->where('sku', $sku)
                        ->first();

                    if (! $sourceProduct) {
                        return json_encode([
                            'error'        => "SKU not found: {$sku}. Ask the user to confirm one of did_you_mean before searching again; never substitute a different product silently.",
                            'did_you_mean' => $this->closestSkus($sku),
                        ]);
                    }
                } elseif ($this->context->hasProductContext()) {
                    // Default to the product the user is currently editing.
                    $sourceProduct = DB::table('products')
                        ->select('id', 'sku', 'type', 'values', 'attribute_family_id')
                        ->where('id', $this->context->productId)
                        ->first();
                }

                $queryText = trim((string) $query);

                if ($queryText === '' && $sourceProduct) {
                    $sourceValues = json_decode((string) $sourceProduct->values, true) ?? [];
                    $sourceName = $sourceValues['channel_locale_specific'][$this->context->channel][$this->context->locale]['name']
                        ?? $sourceValues['common']['url_key']
                        ?? $sourceProduct->sku;

                    $queryText = implode(' | ', [
                        $sourceProduct->sku,
                        $sourceName,
                        $sourceProduct->type,
                    ]);
                }

                if ($queryText === '') {
                    return json_encode(['error' => 'Either query or sku is required.']);
                }

                $familyScoped = $sameFamilyOnly
                    && $sourceProduct
                    && ! empty($sourceProduct->attribute_family_id);

                $knnRanked = $this->embeddingSimilarityService->rankProducts(
                    $queryText,
                    $limit + 1,
                    $familyScoped ? (int) $sourceProduct->attribute_family_id : null,
                );

                if ($knnRanked !== []) {
                    return $this->presentKnnResults($knnRanked, $sourceProduct, $familyScoped, $queryText, $limit);
                }

                $prefix = DB::getTablePrefix();

                $qb = DB::table('products as p')
                    ->leftJoin('attribute_families as af', 'af.id', '=', 'p.attribute_family_id')
                    ->select('p.id', 'p.sku', 'p.type', 'p.status', DB::raw(DB::getQueryGrammar()->wrap('p.values')), 'af.code as family_code')
                    ->orderByDesc('p.id')
                    ->limit($poolLimit);

                if ($sourceProduct) {
                    $qb->where('p.id', '!=', $sourceProduct->id);
                }

                if ($familyScoped) {
                    $qb->where('p.attribute_family_id', $sourceProduct->attribute_family_id);
                }

                $products = $qb->get();

                if ($products->isEmpty()) {
                    return json_encode([
                        'total'                 => 0,
                        'products'              => [],
                        'scoped_to_same_family' => $familyScoped,
                    ]);
                }

                $editBaseUrl = route('admin.catalog.products.edit', ['id' => '__ID__']);

                $context = $this->context;

                $rows = $products->map(function ($p) use ($context, $editBaseUrl): array {
                    $values = json_decode((string) $p->values, true) ?? [];
                    $name = $values['channel_locale_specific'][$context->channel][$context->locale]['name']
                        ?? $values['common']['url_key']
                        ?? '(unnamed)';

                    return [
                        'id'       => $p->id,
                        'sku'      => $p->sku,
                        'name'     => $name,
                        'type'     => $p->type,
                        'status'   => $p->status ? 'active' : 'inactive',
                        'family'   => $p->family_code,
                        'edit_url' => str_replace('__ID__', (string) $p->id, $editBaseUrl),
                    ];
                })->values();

                $documents = $rows->map(fn ($item): string => implode(' | ', [
                    $item['sku'],
                    $item['name'],
                    $item['type'],
                    (string) $item['family'],
                    $item['status'],
                ]))->all();

                $embeddingPlatform = $this->embeddingSimilarityService->resolvePlatform($this->context->platform);
                $ranking = 'semantic';
                $note = null;

                try {
                    $ranked = $embeddingPlatform instanceof MagicAIPlatform
                        ? $this->embeddingSimilarityService->rankOrFail($queryText, $documents, $limit, $embeddingPlatform)
                        : [];
                } catch (\Throwable $e) {
                    $ranked = [];
                    $note = $this->embeddingFailureNote($embeddingPlatform, AiErrorResolver::resolve($e)['message']);
                }

                if ($ranked === []) {
                    $ranking = 'keyword';
                    $note ??= $this->embeddingFailureNote($embeddingPlatform, 'the provider returned no vectors');
                    $ranked = $this->rankByKeywords($queryText, $documents, $limit);
                }

                $results = [];

                foreach ($ranked as $item) {
                    $index = $item['index'];

                    if (! isset($rows[$index])) {
                        continue;
                    }

                    $row = $rows[$index];
                    $row['similarity_score'] = $item['score'];
                    $results[] = $row;
                }

                return json_encode(array_filter([
                    'total'                 => count($results),
                    'products'              => $results,
                    'query'                 => $queryText,
                    'scoped_to_same_family' => $familyScoped,
                    'ranking'               => $ranking,
                    'embedding_platform'    => $ranking === 'semantic' ? $embeddingPlatform?->label : null,
                    'note'                  => $note,
                ], fn ($value): bool => ! is_null($value)));
            }

            /**
             * Explain to the model why semantic ranking was not used, naming the
             * platforms involved so the reply reflects the real backend state.
             */
            private function embeddingFailureNote(?MagicAIPlatform $embeddingPlatform, string $reason): string
            {
                $fallback = 'Results are ranked by keyword overlap of SKU, name, type and family instead. Tell the user this plainly.';

                if ($embeddingPlatform instanceof MagicAIPlatform) {
                    return sprintf('AI semantic similarity was not used because the embeddings request to platform "%s" failed: %s. %s', $embeddingPlatform->label, $reason, $fallback);
                }

                $chatPlatform = $this->context->platform;
                $rejection = $this->embeddingSimilarityService->embeddingRejection($chatPlatform) ?? EmbeddingRejection::NoEmbeddingsApi;

                $note = sprintf(
                    'AI semantic similarity was not used because the chat platform "%s" (%s) cannot create embeddings: %s, and no other active AI platform can. %s',
                    $chatPlatform->label,
                    AiProvider::tryFrom((string) $chatPlatform->provider)?->label() ?? $chatPlatform->provider,
                    $rejection->describe(),
                    $fallback,
                );

                if ($rejection !== EmbeddingRejection::NoEmbeddingsApi) {
                    return $note;
                }

                $capable = collect(AiProvider::cases())
                    ->filter(fn (AiProvider $provider): bool => $provider->supportsEmbeddings())
                    ->map(fn (AiProvider $provider): string => $provider->label())
                    ->implode(', ');

                return "{$note} To enable semantic similarity, activate a platform from one of these providers under Magic AI platforms: {$capable}.";
            }

            /**
             * Rank documents by token overlap (Jaccard) with the query.
             *
             * @param  array<int, string>  $documents
             * @return array<int, array{index: int, score: float}>
             */
            private function rankByKeywords(string $queryText, array $documents, int $limit): array
            {
                $queryTokens = $this->tokens($queryText);
                $scores = [];

                foreach ($documents as $index => $document) {
                    $tokens = $this->tokens($document);
                    $union = count(array_unique(array_merge($queryTokens, $tokens)));
                    $score = $union === 0 ? 0.0 : count(array_intersect($queryTokens, $tokens)) / $union;

                    if ($score > 0) {
                        $scores[] = ['index' => $index, 'score' => round($score, 4)];
                    }
                }

                usort($scores, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

                return array_slice($scores, 0, $limit);
            }

            /**
             * @return array<int, string>
             */
            private function tokens(string $text): array
            {
                return array_values(array_unique(array_filter($this->words($text), fn (string $part): bool => mb_strlen($part) > 1)));
            }

            /**
             * Split text into lowercase letter/number runs.
             *
             * @return array<int, string>
             */
            private function words(string $text, int $limit = -1): array
            {
                return preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), $limit, PREG_SPLIT_NO_EMPTY) ?: [];
            }

            /**
             * Existing SKUs closest to a mistyped one, sharing its leading segment.
             *
             * @return array<int, string>
             */
            private function closestSkus(string $sku): array
            {
                $needle = mb_strtolower($sku);
                $prefix = $this->words($needle, 2)[0] ?? '';

                if ($prefix === '') {
                    return [];
                }

                $maxDistance = max(3, intdiv(strlen($needle), 3));

                return $this->productRepository->skusStartingWith($prefix, 500)
                    ->map(fn (string $candidate): array => [$candidate, levenshtein($needle, mb_strtolower($candidate))])
                    ->filter(fn (array $pair): bool => $pair[1] <= $maxDistance)
                    ->sortBy(fn (array $pair): int => $pair[1])
                    ->take(5)
                    ->map(fn (array $pair): string => $pair[0])
                    ->values()
                    ->all();
            }

            /**
             * Hydrate and present kNN hits from the persistent vector store.
             *
             * @param  array<int, array{product_id: int, score: float}>  $ranked
             */
            private function presentKnnResults(array $ranked, ?object $sourceProduct, bool $familyScoped, string $queryText, int $limit): string
            {
                $scores = [];

                foreach ($ranked as $hit) {
                    if ($sourceProduct && $hit['product_id'] === (int) $sourceProduct->id) {
                        continue;
                    }

                    $scores[$hit['product_id']] = $hit['score'];
                }

                $scores = array_slice($scores, 0, $limit, true);

                if ($scores === []) {
                    return json_encode([
                        'total'                 => 0,
                        'products'              => [],
                        'query'                 => $queryText,
                        'scoped_to_same_family' => $familyScoped,
                    ]);
                }

                $prefix = DB::getTablePrefix();

                $products = DB::table('products as p')
                    ->leftJoin('attribute_families as af', 'af.id', '=', 'p.attribute_family_id')
                    ->select('p.id', 'p.sku', 'p.type', 'p.status', DB::raw(DB::getQueryGrammar()->wrap('p.values')), 'af.code as family_code')
                    ->whereIn('p.id', array_keys($scores))
                    ->get()
                    ->keyBy('id');

                $editBaseUrl = route('admin.catalog.products.edit', ['id' => '__ID__']);
                $context = $this->context;
                $results = [];

                foreach ($scores as $productId => $score) {
                    $p = $products->get($productId);

                    if (! $p) {
                        continue;
                    }

                    $values = json_decode((string) $p->values, true) ?? [];
                    $name = $values['channel_locale_specific'][$context->channel][$context->locale]['name']
                        ?? $values['common']['url_key']
                        ?? '(unnamed)';

                    $results[] = [
                        'id'               => $p->id,
                        'sku'              => $p->sku,
                        'name'             => $name,
                        'type'             => $p->type,
                        'status'           => $p->status ? 'active' : 'inactive',
                        'family'           => $p->family_code,
                        'edit_url'         => str_replace('__ID__', (string) $p->id, $editBaseUrl),
                        'similarity_score' => $score,
                    ];
                }

                return json_encode([
                    'total'                 => count($results),
                    'products'              => $results,
                    'query'                 => $queryText,
                    'scoped_to_same_family' => $familyScoped,
                    'source'                => 'vector_store',
                ]);
            }
        };
    }
}
