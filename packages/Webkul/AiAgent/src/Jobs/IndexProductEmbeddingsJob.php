<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;

/**
 * Embeds a batch of products' textual values and upserts them into the
 * persistent Elasticsearch vector store.
 *
 * Skips products whose embedded text is unchanged (content hash match), so
 * re-runs are cheap and the indexer is resumable. Unchanged products whose
 * stored attribute family is stale get a metadata-only update instead.
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
    ): void {
        if (! $index->isEnabled() || $this->productIds === []) {
            return;
        }

        $documents = $this->buildDocuments($documentBuilder);

        if ($documents === []) {
            return;
        }

        $existing = $index->existingDocuments(array_column($documents, 'product_id'));

        $staleFamilies = $this->staleAttributeFamilies($existing, $documents);

        $documents = $this->rejectUnchanged($existing, $documents);

        if ($documents === [] && $staleFamilies === []) {
            return;
        }

        $index->ensureIndex();

        $index->bulkUpdateAttributeFamilies($staleFamilies);

        if ($documents === []) {
            return;
        }

        try {
            $response = Embeddings::for(array_column($documents, 'text'))
                ->cache()
                ->generate();

            $vectors = $response->embeddings;
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
                'product_id'          => $document['product_id'],
                'sku'                 => $document['sku'],
                'attribute_family_id' => $document['attribute_family_id'],
                'content_hash'        => $document['content_hash'],
                'embedding'           => $vector,
            ];
        }

        $index->bulkUpsert($upserts);
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
     * Drop documents whose stored content hash already matches, avoiding
     * needless embedding calls and index writes.
     *
     * @param  array<int, array{content_hash: string, attribute_family_id: ?int}>  $existing
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>
     */
    protected function rejectUnchanged(array $existing, array $documents): array
    {
        return array_values(array_filter(
            $documents,
            fn (array $document): bool => ($existing[$document['product_id']]['content_hash'] ?? null) !== $document['content_hash'],
        ));
    }

    /**
     * Attribute families to repair on unchanged documents whose stored family
     * is missing or outdated, keyed by product id.
     *
     * @param  array<int, array{content_hash: string, attribute_family_id: ?int}>  $existing
     * @param  array<int, array{product_id: int, sku: ?string, text: string, content_hash: string, attribute_family_id: ?int}>  $documents
     * @return array<int, ?int>
     */
    protected function staleAttributeFamilies(array $existing, array $documents): array
    {
        $stale = [];

        foreach ($documents as $document) {
            $stored = $existing[$document['product_id']] ?? null;

            if ($stored === null || $stored['content_hash'] !== $document['content_hash']) {
                continue;
            }

            if ($stored['attribute_family_id'] !== $document['attribute_family_id']) {
                $stale[$document['product_id']] = $document['attribute_family_id'];
            }
        }

        return $stale;
    }
}
