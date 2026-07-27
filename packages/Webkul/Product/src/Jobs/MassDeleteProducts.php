<?php

namespace Webkul\Product\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Product\Repositories\ProductRepository;

class MassDeleteProducts implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int>  $productIds
     */
    public function __construct(protected array $productIds) {}

    public function handle(ProductRepository $productRepository): void
    {
        $productRepository->massDelete($this->productIds);
    }
}
