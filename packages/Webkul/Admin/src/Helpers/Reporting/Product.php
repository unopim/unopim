<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Product\Repositories\ProductRepository;

class Product extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * This method calculates and returns the total number of products in the system.
     *
     * @return int The total number of products.
     */
    public function getTotalProducts(): int
    {
        return $this->productRepository
            ->resetModel()
            ->count();
    }
}
