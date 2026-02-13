<?php

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\Storage;
use Webkul\Tenant\Filesystem\TenantStorage;

class ProductObserver
{
    /**
     * Handle the Product "deleted" event.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function deleted($product)
    {
        Storage::deleteDirectory(TenantStorage::path('product/'.$product->id));
    }
}
