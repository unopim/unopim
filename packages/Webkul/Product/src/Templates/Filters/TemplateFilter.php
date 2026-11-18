<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

interface TemplateFilter
{
    /**
     * Determine if the filter can be applied to the given attribute type.
     */
    public function supports(string $attributeType): bool;

    /**
     * Determine if the filter accepts the given argument.
     */
    public function acceptsArgument(?string $argument): bool;

    /**
     * Apply the filter to the value, returning null when it cannot be applied.
     */
    public function apply(string $value, ?string $argument, string $locale): ?string;
}
