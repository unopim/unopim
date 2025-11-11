<?php

namespace Webkul\Completeness\Observers;

use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Product\Models\Product as Products;

class Product
{
    /**
     * bool flag to manage observer functionality
     */
    protected static bool $isEnabled = true;

    /**
     * Enable the observer functionality.
     */
    public static function enable(): void
    {
        self::$isEnabled = true;
    }

    /**
     * Disable the observer functionality.
     */
    public static function disable(): void
    {
        self::$isEnabled = false;
    }

    /**
     * Get the current state of the observer functionality.
     */
    public static function isEnabled(): bool
    {
        return self::$isEnabled;
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Products $product)
    {
        if (self::$isEnabled) {
            ProductCompletenessJob::dispatch([$product->id]);
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Products $product)
    {
        if (self::$isEnabled) {
            ProductCompletenessJob::dispatch([$product->id]);
        }
    }
}
