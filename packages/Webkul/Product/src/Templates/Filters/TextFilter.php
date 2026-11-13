<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

abstract class TextFilter implements TemplateFilter
{
    public function supports(string $attributeType): bool
    {
        return true;
    }

    public function acceptsArgument(?string $argument): bool
    {
        return $argument === null;
    }
}
