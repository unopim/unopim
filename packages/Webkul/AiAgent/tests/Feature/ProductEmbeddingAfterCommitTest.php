<?php

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\AiAgent\Jobs\DeleteProductEmbeddingJob;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;

beforeEach(function (): void {
    config(['queue.default' => 'database']);

    app()->instance(ProductEmbeddingIndex::class, new class extends ProductEmbeddingIndex
    {
        public function isEnabled(): bool
        {
            return true;
        }
    });

    $this->queuedEmbeddingJobs = embeddingAfterCommitQueuedJobs();
});

/**
 * Record the class and product ids of every embedding job that reaches the queue.
 *
 * @return ArrayObject<int, array{0: class-string, 1: int}>
 */
function embeddingAfterCommitQueuedJobs(): ArrayObject
{
    $queued = new ArrayObject;

    Event::listen(JobQueued::class, function (JobQueued $event) use ($queued): void {
        if ($event->job instanceof IndexProductEmbeddingsJob) {
            foreach ((fn (): array => $this->productIds)->call($event->job) as $productId) {
                $queued[] = [IndexProductEmbeddingsJob::class, $productId];
            }
        }

        if ($event->job instanceof DeleteProductEmbeddingJob) {
            $queued[] = [DeleteProductEmbeddingJob::class, (fn (): int => $this->productId)->call($event->job)];
        }
    });

    return $queued;
}

it('queues the embedding job of a created product only after the transaction commits', function (): void {
    $product = DB::transaction(function (): Product {
        $product = ProductProxy::factory()->create();

        expect($this->queuedEmbeddingJobs->getArrayCopy())->not->toContain([IndexProductEmbeddingsJob::class, $product->id]);

        return $product;
    });

    expect($this->queuedEmbeddingJobs->getArrayCopy())->toContain([IndexProductEmbeddingsJob::class, $product->id]);
});

it('queues the embedding job of an updated product only after the transaction commits', function (): void {
    $product = ProductProxy::factory()->create();

    $this->queuedEmbeddingJobs->exchangeArray([]);

    DB::transaction(function () use ($product): void {
        $product->update(['sku' => $product->sku.'-renamed']);

        expect($this->queuedEmbeddingJobs->getArrayCopy())->not->toContain([IndexProductEmbeddingsJob::class, $product->id]);
    });

    expect($this->queuedEmbeddingJobs->getArrayCopy())->toContain([IndexProductEmbeddingsJob::class, $product->id]);
});

it('queues the embedding removal of a deleted product only after the transaction commits', function (): void {
    $product = ProductProxy::factory()->create();

    DB::transaction(function () use ($product): void {
        $product->delete();

        expect($this->queuedEmbeddingJobs->getArrayCopy())->not->toContain([DeleteProductEmbeddingJob::class, $product->id]);
    });

    expect($this->queuedEmbeddingJobs->getArrayCopy())->toContain([DeleteProductEmbeddingJob::class, $product->id]);
});

it('does not queue an embedding job for a product whose transaction rolls back', function (): void {
    $productId = null;

    try {
        DB::transaction(function () use (&$productId): void {
            $productId = ProductProxy::factory()->create()->id;

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect($productId)->not->toBeNull()
        ->and($this->queuedEmbeddingJobs->getArrayCopy())->not->toContain([IndexProductEmbeddingsJob::class, $productId]);
});
