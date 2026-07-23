<?php

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleted" event.
     *
     * @param  Product  $product
     */
    public function deleted($product): void
    {
        Storage::deleteDirectory('product/'.$product->id);
    }
}
