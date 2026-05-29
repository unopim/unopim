<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Product\Repositories\ProductReviewRepository;

class Review
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected ProductReviewRepository $productReviewRepository) {}

    /**
     * After review is updated
     *
     * @param  \Webkul\Product\Contracts\Review  $review
     */
    public function afterUpdate(mixed $review): void
    {
        ResponseCache::forget('/'.$review->product->url_key);
    }

    /**
     * Before review is deleted
     *
     * @param  \Webkul\Product\Contracts\Review  $review
     */
    public function beforeDelete(int $reviewId): void
    {
        $review = $this->productReviewRepository->find($reviewId);

        ResponseCache::forget('/'.$review->product->url_key);
    }
}
