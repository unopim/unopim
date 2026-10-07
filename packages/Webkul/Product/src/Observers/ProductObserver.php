<?php

namespace Webkul\Product\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class ProductObserver implements ShouldHandleEventsAfterCommit
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
