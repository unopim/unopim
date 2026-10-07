<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;

/**
 * Embeds a batch of products' textual values and upserts them into the
 * persistent Elasticsearch vector store.
 *
 * Skips products whose embedded text and embedding model are unchanged
 * (content hash match), so re-runs are cheap and the indexer is resumable.
 * Switching the embedding provider or model changes every hash, so the next
 * run re-embeds the catalog instead of mixing vectors from two models.
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
     */
    public function handle(
        ProductEmbeddingIndex $index,
        ProductEmbeddingDocumentBuilder $documentBuilder,
        EmbeddingSimilarityService $similarityService,
    ): void {
        if (! $index->isEnabled() || $this->productIds === []) {
            return;
        }

        $documents = $this->buildDocuments($documentBuilder);

        if ($documents === []) {
            return;
        }

        $platform = $similarityService->resolvePlatform(dimensions: $index->dimensions());
        $fingerprint = $similarityService->embeddingFingerprint($platform, $index->dimensions());

        $documents = $this->rejectUnchanged($index, $this->stampFingerprint($documents, $fingerprint));

        if ($documents === []) {
            return;
        }

        try {
            $index->ensureIndex();

            $vectors = $similarityService->generateEmbeddings(
                array_column($documents, 'text'),
                $platform,
                $index->dimensions(),
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
                'content_hash'          => $document['content_hash'],
                'embedding_fingerprint' => $fingerprint,
                'embedding'             => $vector,
            ];
        }

        $index->bulkUpsert($upserts);
    }

    /**
     * Load the batch rows and build their embeddable documents.
     *
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>
     */
    protected function buildDocuments(ProductEmbeddingDocumentBuilder $documentBuilder): array
    {
        $rows = DB::table('products')
            ->whereIn('id', array_map(intval(...), $this->productIds))
            ->select('id', 'sku', 'values', 'attribute_family_id')
            ->get();

        $documents = [];

        foreach ($rows as $row) {
            $document = $documentBuilder->build((int) $row->id, $row->sku, $row->values);

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
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>
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
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>
     */
    protected function rejectUnchanged(ProductEmbeddingIndex $index, array $documents): array
    {
        $existingHashes = $index->existingContentHashes(array_column($documents, 'product_id'));

        return array_values(array_filter(
            $documents,
            fn (array $document): bool => ($existingHashes[$document['product_id']] ?? null) !== $document['content_hash'],
        ));
    }
}
