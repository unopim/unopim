<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class Expression
{
    /**
     * Create a new template expression instance.
     */
    public function __construct(
        public string $label,
        public string $attributeCode,
        public ?string $currency = null,
        public array $filters = [],
    ) {}
}
