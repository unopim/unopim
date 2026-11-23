<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

abstract class TextFilter implements TemplateFilter
{
    /**
     * Determine if the filter supports the attribute type, which is true for every type.
     */
    public function supports(string $attributeType): bool
    {
        return true;
    }

    /**
     * Determine if the argument is acceptable, which is only when no argument is given.
     */
    public function acceptsArgument(?string $argument): bool
    {
        return $argument === null;
    }
}
