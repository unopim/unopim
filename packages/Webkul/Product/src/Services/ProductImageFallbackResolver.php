<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\ProductImageFallbackResolver as ProductImageFallbackResolverContract;
use Webkul\Product\Models\Product;

/**
 * Core has no other place to find a display image once no `image`-type
 * attribute has a value, so the default binding is a deliberate no-op. An
 * optional asset-management extension overrides this binding (see the
 * contract's docblock) to add its own source.
 */
class ProductImageFallbackResolver implements ProductImageFallbackResolverContract
{
    public function resolveFallbackUrl(Product $product, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): ?string
    {
        return null;
    }
}
