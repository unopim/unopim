<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

interface TemplateFilter
{
    public function supports(string $attributeType): bool;

    public function acceptsArgument(?string $argument): bool;

    public function apply(string $value, ?string $argument, string $locale): ?string;
}
