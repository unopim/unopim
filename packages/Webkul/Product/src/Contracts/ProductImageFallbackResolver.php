<?php

namespace Webkul\Product\Contracts;

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
 *
 * Type-hinted against the `Webkul\Product\Contracts\Product` marker, not the
 * concrete model, so an installation that swaps the product model via
 * Concord can still implement this public extension point.
 */
interface ProductImageFallbackResolver
{
    /**
     * Resolve a ready-to-use display image URL for the product, or null.
     */
    public function resolveFallbackUrl(Product $product, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): ?string;
}
