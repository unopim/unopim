<?php

namespace Webkul\Admin\Observers;

use Webkul\Admin\Helpers\Dashboard;
use Webkul\Product\Contracts\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        Dashboard::invalidateProductCache();
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        Dashboard::invalidateProductCache();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        Dashboard::invalidateProductCache();
    }
}
