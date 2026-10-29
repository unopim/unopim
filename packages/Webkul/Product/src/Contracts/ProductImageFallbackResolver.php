<?php

namespace Webkul\Product\Contracts;

use Webkul\Product\Models\Product;

/**
 * Resolves a display image URL for a product when no classic `image`-type
 * attribute has a value anywhere in its variant tree.
 *
 * Core only knows about the `image`/`gallery` attribute types, whose stored
 * value is a storage-relative path. An optional asset-management extension
 * (for example the DAM package) can bind its own implementation over this
 * contract to resolve a product's linked asset into a ready-to-use URL,
 * without core ever depending on that extension's classes or tables.
 *
 * The default binding (see `ProductServiceProvider::registerBindings()`)
 * always returns null, which keeps `normalizeWithImage()` behaving exactly
 * as before for every installation that has nothing bound here.
 */
interface ProductImageFallbackResolver
{
    /**
     * @return string|null A ready-to-use URL, or null when this resolver has nothing to offer.
     */
    public function resolveFallbackUrl(Product $product, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): ?string;
}
