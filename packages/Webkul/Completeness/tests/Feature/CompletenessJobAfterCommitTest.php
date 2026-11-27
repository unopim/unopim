<?php

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Completeness\Observers\Product as CompletenessProductObserver;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;

beforeEach(function (): void {
    config(['queue.default' => 'database']);

    $this->queuedProductIds = completenessAfterCommitQueuedProductIds();
});

afterEach(function (): void {
    CompletenessProductObserver::enable();
});

/**
 * Record the product ids of every completeness job that reaches the queue.
 *
 * @return ArrayObject<int, int>
 */
function completenessAfterCommitQueuedProductIds(): ArrayObject
{
    $queued = new ArrayObject;

    Event::listen(JobQueued::class, function (JobQueued $event) use ($queued): void {
        if ($event->job instanceof ProductCompletenessJob) {
            foreach ((fn (): array => $this->productIds)->call($event->job) as $productId) {
                $queued[] = $productId;
            }
        }
    });

    return $queued;
}

it('queues the completeness job of a created product only after the transaction commits', function (): void {
    $product = DB::transaction(function (): Product {
        $product = ProductProxy::factory()->create();

        expect($this->queuedProductIds->getArrayCopy())->not->toContain($product->id);

        return $product;
    });

    expect($this->queuedProductIds->getArrayCopy())->toContain($product->id);
});

it('queues the completeness job of an updated product only after the transaction commits', function (): void {
    $product = ProductProxy::factory()->create();

    $this->queuedProductIds->exchangeArray([]);

    DB::transaction(function () use ($product): void {
        $product->update(['status' => ! $product->status]);

        expect($this->queuedProductIds->getArrayCopy())->not->toContain($product->id);
    });

    expect($this->queuedProductIds->getArrayCopy())->toContain($product->id);
});

it('does not queue a completeness job for a product whose transaction rolls back', function (): void {
    $productId = null;

    try {
        DB::transaction(function () use (&$productId): void {
            $productId = ProductProxy::factory()->create()->id;

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect($productId)->not->toBeNull()
        ->and($this->queuedProductIds->getArrayCopy())->not->toContain($productId);
});

it('queues the completeness job straight away when no transaction is open', function (): void {
    $product = ProductProxy::factory()->create();

    expect($this->queuedProductIds->getArrayCopy())->toContain($product->id);
});

it('does not queue a completeness job for a product saved while the observer was disabled', function (): void {
    $product = DB::transaction(function (): Product {
        CompletenessProductObserver::disable();

        $product = ProductProxy::factory()->create();

        CompletenessProductObserver::enable();

        return $product;
    });

    expect($this->queuedProductIds->getArrayCopy())->not->toContain($product->id);
});
