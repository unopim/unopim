<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ProviderOverrides;
use Webkul\MagicAI\Services\ScopedProviderConfig;

/**
 * Semantic similarity scoring using laravel/ai embeddings.
 */
class EmbeddingSimilarityService
{
    public function __construct(protected ?ProductEmbeddingIndex $productEmbeddingIndex = null) {}

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
            return $pending->generate()->embeddings;
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
        if ($preferred instanceof MagicAIPlatform && $this->canEmbed($preferred, $dimensions)) {
            return $preferred;
        }

        return resolve(MagicAIPlatformRepository::class)
            ->getActiveList()
            ->filter(fn (MagicAIPlatform $platform): bool => $this->canEmbed($platform, $dimensions))
            ->sortBy([['is_default', 'desc'], ['id', 'asc']])
            ->first();
    }

    /**
     * Azure addresses embeddings by deployment name, and laravel/ai reads it
     * from `embedding_deployment`, not the chat `deployment`.
     */
    protected function canEmbed(MagicAIPlatform $platform, ?int $dimensions = null): bool
    {
        $provider = AiProvider::tryFrom((string) $platform->provider);

        if (! $platform->status || ! $provider?->supportsEmbeddings() || $platform->apiKeyError() !== null) {
            return false;
        }

        $fixedDimensions = $provider->fixedEmbeddingDimensions();

        if (! is_null($dimensions) && ! is_null($fixedDimensions) && $fixedDimensions !== $dimensions) {
            return false;
        }

        return $provider !== AiProvider::Azure
            || filled(ProviderOverrides::decode($platform->extras)['embedding_deployment'] ?? null);
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
            $queryVector = $this->generateEmbeddings([$query], $this->resolvePlatform(dimensions: $index->dimensions()), $index->dimensions())[0] ?? null;

            if (! is_array($queryVector) || $queryVector === []) {
                return [];
            }

            return $index->searchSimilar($queryVector, $limit ?? 10, $attributeFamilyId);
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
