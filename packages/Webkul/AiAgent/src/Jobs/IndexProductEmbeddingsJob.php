<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Embeds a batch of products' textual values and upserts them into the
 * persistent Elasticsearch vector store.
 *
 * Skips products whose embedded text and embedding model are unchanged
 * (content hash match), so re-runs are cheap and the indexer is resumable.
 * Switching the embedding provider or model changes every hash, so the next
 * run re-embeds the catalog instead of mixing vectors from two models.
 * Unchanged products whose stored attribute family is stale get a
 * metadata-only update instead.
 */
class IndexProductEmbeddingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * @param  array<int, int>  $productIds
     */
    public function __construct(protected array $productIds)
    {
        $this->queue = 'default';
    }

    /**
     * Embed the batch and upsert the vectors into the embedding index.
     *
     * @throws \RuntimeException when Elasticsearch rejects any document, so the queue retries the batch
     */
    public function handle(
        ProductEmbeddingIndex $index,
        ProductEmbeddingDocumentBuilder $documentBuilder,
    ): void {
        if (! $index->isEnabled() || $this->productIds === []) {
            return;
        }

        $documents = $this->buildDocuments($documentBuilder);

        if ($documents === []) {
            return;
        }

        $similarityService = resolve(EmbeddingSimilarityService::class);
        $platform = $similarityService->resolvePlatform(dimensions: $index->dimensions());
        $fingerprint = $similarityService->embeddingFingerprint($platform, $index->dimensions());
        $documents = $this->stampFingerprint($documents, $fingerprint);

        [$documents, $staleFamilies] = $this->partitionDocuments(
            $index->existingDocuments(array_column($documents, 'product_id')),
            $documents,
        );

        if ($documents === [] && $staleFamilies === []) {
            return;
        }

        $index->ensureIndex();

        $failedIds = $index->bulkUpdateAttributeFamilies($staleFamilies);

        if ($documents !== []) {
            $failedIds = [...$failedIds, ...$index->bulkUpsert($this->embed($documents, $similarityService, $platform, $index->dimensions(), $fingerprint))];
        }

        if ($failedIds !== []) {
            throw new \RuntimeException('Elasticsearch rejected product embedding documents for product ids: '.implode(', ', array_unique($failedIds)).'.');
        }
    }

    /**
     * Generate vectors for the changed documents and shape them for upsert.
     *
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, attribute_family_id: ?int, content_hash: string, embedding_fingerprint: string, embedding: array<int, float>}>
     */
    protected function embed(
        array $documents,
        EmbeddingSimilarityService $similarityService,
        ?MagicAIPlatform $platform,
        int $dimensions,
        string $fingerprint,
    ): array {
        try {
            $vectors = $similarityService->generateEmbeddings(
                array_column($documents, 'text'),
                $platform,
                $dimensions,
            );
        } catch (\Throwable $e) {
            Log::channel('elasticsearch')->error('Failed to generate product embeddings for vector store.', [
                'product_ids' => array_column($documents, 'product_id'),
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }

        $upserts = [];

        foreach (array_values($documents) as $position => $document) {
            $vector = $vectors[$position] ?? null;
            if (! is_array($vector)) {
                continue;
            }
            if ($vector === []) {
                continue;
            }

            $upserts[] = [
                'product_id'            => $document['product_id'],
                'sku'                   => $document['sku'],
                'attribute_family_id'   => $document['attribute_family_id'],
                'content_hash'          => $document['content_hash'],
                'embedding_fingerprint' => $fingerprint,
                'embedding'             => $vector,
            ];
        }

        return $upserts;
    }

    /**
     * Load the batch rows and build their embeddable documents.
     *
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>
     */
    protected function buildDocuments(ProductEmbeddingDocumentBuilder $documentBuilder): array
    {
        $rows = DB::table('products')
            ->whereIn('id', array_map(intval(...), $this->productIds))
            ->select('id', 'sku', 'values', 'attribute_family_id', 'parent_id')
            ->get();

        $parentIds = $rows->pluck('parent_id')->filter()->map(intval(...))->unique()->values()->all();

        $parentValues = $parentIds === [] ? collect() : DB::table('products')
            ->whereIn('id', $parentIds)
            ->pluck('values', 'id');

        $built = $documentBuilder->buildMany($rows->map(fn (object $row): array => [
            'id'            => (int) $row->id,
            'sku'           => $row->sku,
            'values'        => $row->values,
            'parent_values' => $row->parent_id !== null ? $parentValues->get((int) $row->parent_id) : null,
        ])->values()->all());

        $documents = [];

        foreach ($rows->values() as $position => $row) {
            $document = $built[$position];

            if ($document['text'] === '') {
                continue;
            }

            $document['attribute_family_id'] = $row->attribute_family_id !== null ? (int) $row->attribute_family_id : null;

            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * Fold the embedding model fingerprint into each content hash.
     *
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>
     */
    protected function stampFingerprint(array $documents, string $fingerprint): array
    {
        return array_map(
            fn (array $document): array => [...$document, 'content_hash' => hash('sha256', $document['content_hash'].'|'.$fingerprint)],
            $documents,
        );
    }

    /**
     * Drop documents whose stored content hash already matches, avoiding
     * needless embedding calls and index writes.
     *
     * @deprecated Use partitionDocuments(); removal in the next major.
     *
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>
     */
    protected function rejectUnchanged(ProductEmbeddingIndex $index, array $documents): array
    {
        return $this->partitionDocuments(
            $index->existingDocuments(array_column($documents, 'product_id')),
            $documents,
        )[0];
    }

    /**
     * Split documents into those needing (re-)embedding because their content
     * hash changed, and the attribute families to repair on unchanged documents
     * whose stored family is missing or outdated (keyed by product id).
     *
     * @param  array<int, array{content_hash: string, attribute_family_id: ?int}>  $existing
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array{0: array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>, 1: array<int, ?int>}
     */
    protected function partitionDocuments(array $existing, array $documents): array
    {
        $changed = [];
        $staleFamilies = [];

        foreach ($documents as $document) {
            $stored = $existing[$document['product_id']] ?? null;

            if ($stored === null || $stored['content_hash'] !== $document['content_hash']) {
                $changed[] = $document;

                continue;
            }

            $attributeFamilyId = $document['attribute_family_id'] ?? null;

            if ($stored['attribute_family_id'] !== $attributeFamilyId) {
                $staleFamilies[$document['product_id']] = $attributeFamilyId;
            }
        }

        return [$changed, $staleFamilies];
    }
}
