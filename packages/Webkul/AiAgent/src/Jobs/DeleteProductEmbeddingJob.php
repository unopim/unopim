<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;

/**
 * Removes a deleted product's embedding document from the vector store.
 */
class DeleteProductEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(protected int $productId)
    {
        $this->queue = 'default';
    }

    /**
     * Delete the embedding document for the product.
     */
    public function handle(ProductEmbeddingIndex $index): void
    {
        if (! $index->isEnabled()) {
            return;
        }

        $index->deleteByProductId($this->productId);
    }
}
