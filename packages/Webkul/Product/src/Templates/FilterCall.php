<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class FilterCall
{
    /**
     * Create a new filter call instance.
     */
    public function __construct(
        public string $name,
        public ?string $argument = null,
    ) {}
}
