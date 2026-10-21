<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Ai;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Providers\Provider;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Enums\EmbeddingRejection;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ProviderOverrides;
use Webkul\MagicAI\Services\ScopedProviderConfig;

/**
 * Semantic similarity scoring using laravel/ai embeddings.
 */
class EmbeddingSimilarityService
{
    public function __construct(
        protected ?ProductEmbeddingIndex $productEmbeddingIndex = null,
        protected ?MagicAIPlatformRepository $magicAIPlatformRepository = null,
    ) {}

    /**
     * Rank documents by similarity to a query text, or [] when embeddings fail.
     *
     * @param  array<int, string>  $documents
     * @return array<int, array{index: int, score: float}>
     */
    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        try {
            return $this->rankOrFail($query, $documents, $limit, $this->resolvePlatform());
        } catch (\Throwable $e) {
            Log::warning('AI similarity ranking failed.', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Rank documents by similarity to a query text through the given platform.
     *
     * @param  array<int, string>  $documents
     * @return array<int, array{index: int, score: float}>
     *
     * @throws \Throwable when the embeddings provider rejects the request
     */
    public function rankOrFail(string $query, array $documents, ?int $limit = null, ?MagicAIPlatform $platform = null): array
    {
        if (trim($query) === '' || $documents === []) {
            return [];
        }

        $vectors = $this->generateEmbeddings(array_merge([$query], $documents), $platform);
        $queryVector = $vectors[0] ?? null;

        if (! is_array($queryVector) || $queryVector === []) {
            return [];
        }

        $scores = [];

        foreach (array_slice($vectors, 1) as $index => $vector) {
            if (! is_array($vector)) {
                continue;
            }
            if ($vector === []) {
                continue;
            }
            $scores[] = [
                'index' => $index,
                'score' => $this->cosine($queryVector, $vector),
            ];
        }

        usort($scores, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        if (! is_null($limit)) {
            return array_slice($scores, 0, max(1, $limit));
        }

        return $scores;
    }

    /**
     * Generate embeddings through the platform, or the laravel/ai default
     * embeddings provider from config/ai.php when no platform is given.
     *
     * Pass $dimensions when the vectors must fit a fixed-size index, since
     * each provider otherwise returns its own default vector size.
     *
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function generateEmbeddings(array $inputs, ?MagicAIPlatform $platform = null, ?int $dimensions = null): array
    {
        $pending = Embeddings::for($inputs)->cache();

        if (! is_null($dimensions)) {
            $pending->dimensions($dimensions);
        }

        if (! $platform instanceof MagicAIPlatform) {
            [$provider, $model] = $this->defaultEmbeddingsProvider();

            return $pending->generate($provider, $model)->embeddings;
        }

        $aiProvider = AiProvider::from($platform->provider);

        return ScopedProviderConfig::run(
            $aiProvider->configKey(),
            $platform->providerOverrides(),
            fn (): array => $pending->generate(provider: $aiProvider->toLab())->embeddings,
        );
    }

    /**
     * Resolve the platform to embed with: the preferred one when its provider
     * has an embeddings API, else the default or first active platform that does.
     *
     * Pass $dimensions when the vectors must fit a fixed-size index, so a
     * provider that cannot return that size is skipped.
     */
    public function resolvePlatform(?MagicAIPlatform $preferred = null, ?int $dimensions = null): ?MagicAIPlatform
    {
        if ($preferred instanceof MagicAIPlatform && is_null($this->embeddingRejection($preferred, $dimensions))) {
            return $preferred;
        }

        return ($this->magicAIPlatformRepository ?? resolve(MagicAIPlatformRepository::class))
            ->getActiveList()
            ->filter(fn (MagicAIPlatform $platform): bool => is_null($this->embeddingRejection($platform, $dimensions)))
            ->sortBy([['is_default', 'desc'], ['id', 'asc']])
            ->first();
    }

    /**
     * Why the platform cannot generate embeddings of the requested size, or
     * null when it can.
     *
     * Azure addresses embeddings by deployment name, and laravel/ai reads it
     * from `embedding_deployment`, not the chat `deployment`.
     */
    public function embeddingRejection(MagicAIPlatform $platform, ?int $dimensions = null): ?EmbeddingRejection
    {
        $provider = AiProvider::tryFrom((string) $platform->provider);
        $fixedDimensions = $provider?->fixedEmbeddingDimensions();

        return match (true) {
            ! $platform->status                                                                                                    => EmbeddingRejection::Inactive,
            ! $provider?->supportsEmbeddings()                                                                                     => EmbeddingRejection::NoEmbeddingsApi,
            $platform->apiKeyError() !== null                                                                                      => EmbeddingRejection::UnreadableApiKey,
            ! is_null($dimensions) && ! is_null($fixedDimensions) && $fixedDimensions !== $dimensions                              => EmbeddingRejection::DimensionsMismatch,
            $provider === AiProvider::Azure && blank(ProviderOverrides::decode($platform->extras)['embedding_deployment'] ?? null) => EmbeddingRejection::MissingEmbeddingDeployment,
            default                                                                                                                => null,
        };
    }

    /**
     * Identify the provider, model and vector size that embeddings generated
     * through the platform come from, so vectors from different embedding
     * models are never compared with each other.
     */
    public function embeddingFingerprint(?MagicAIPlatform $platform, int $dimensions): string
    {
        $describe = fn (string $provider, ?string $model): string => sprintf(
            '%s/%s/%d',
            $provider,
            $model ?? Ai::embeddingProvider($provider)->defaultEmbeddingsModel(),
            $dimensions,
        );

        if (! $platform instanceof MagicAIPlatform) {
            return $describe(...$this->defaultEmbeddingsProvider());
        }

        $aiProvider = AiProvider::from($platform->provider);

        return ScopedProviderConfig::run(
            $aiProvider->configKey(),
            $platform->providerOverrides(),
            fn (): string => $describe($aiProvider->toLab()->value, null),
        );
    }

    /**
     * The first provider and model configured in `ai.default_for_embeddings`.
     *
     * Generation is pinned to it rather than to the whole failover list, so
     * every vector is produced by the model its fingerprint names.
     *
     * @return array{0: string, 1: ?string}
     */
    protected function defaultEmbeddingsProvider(): array
    {
        $providers = Provider::formatProviderAndModelList(config('ai.default_for_embeddings'));
        $provider = (string) array_key_first($providers);

        return [$provider, $providers[$provider]];
    }

    /**
     * Rank products by similarity to a query using the persistent vector store.
     *
     * Runs an Elasticsearch kNN search against pre-indexed product embeddings.
     * Returns [] when the vector store is disabled or on any failure, so
     * callers can fall back to their existing ranking path.
     *
     * @return array<int, array{product_id: int, score: float}> sorted by score descending
     */
    public function rankProducts(string $query, ?int $limit = null, ?int $attributeFamilyId = null): array
    {
        $index = $this->productEmbeddingIndex ?? resolve(ProductEmbeddingIndex::class);

        if (trim($query) === '' || ! $index->isEnabled()) {
            return [];
        }

        try {
            $platform = $this->resolvePlatform(dimensions: $index->dimensions());
            $queryVector = $this->generateEmbeddings([$query], $platform, $index->dimensions())[0] ?? null;

            if (! is_array($queryVector) || $queryVector === []) {
                return [];
            }

            return $index->searchSimilar(
                $queryVector,
                $limit ?? 10,
                $attributeFamilyId,
                $this->embeddingFingerprint($platform, $index->dimensions()),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Compute cosine similarity between two vectors.
     *
     * @param  array<int, float|int>  $a
     * @param  array<int, float|int>  $b
     */
    protected function cosine(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $av = (float) $a[$i];
            $bv = (float) $b[$i];
            $dot += $av * $bv;
            $normA += $av * $av;
            $normB += $bv * $bv;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
