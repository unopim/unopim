<?php

namespace Webkul\Product\Contracts;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Models\Product;
use Webkul\Product\Templates\Results\RenderResult;

interface TemplateRenderer
{
    public function render(string $source, Attribute $attribute, array $values, string $channel, string $locale): RenderResult;

    public function renderProduct(Product $product, Attribute $attribute, string $channel, string $locale): RenderResult;
}
