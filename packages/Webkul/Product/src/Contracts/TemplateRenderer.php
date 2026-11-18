<?php

namespace Webkul\Product\Contracts;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Models\Product;
use Webkul\Product\Templates\Results\RenderResult;

interface TemplateRenderer
{
    /**
     * Render the template source for the attribute using the given values, channel and locale.
     */
    public function render(string $source, Attribute $attribute, array $values, string $channel, string $locale): RenderResult;

    /**
     * Render the attribute's template stored on the product for the given channel and locale.
     */
    public function renderProduct(Product $product, Attribute $attribute, string $channel, string $locale): RenderResult;
}
