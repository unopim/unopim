<?php

declare(strict_types=1);

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        Storage::deleteDirectory('product/'.$product->id);
    }
}
