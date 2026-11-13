<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class ParsedTemplate
{
    public function __construct(
        public array $segments,
        public array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function expressions(): array
    {
        return array_values(array_filter(
            $this->segments,
            fn (string|Expression $segment): bool => $segment instanceof Expression,
        ));
    }

    public function attributeCodes(): array
    {
        return array_values(array_unique(array_map(
            fn (Expression $expression): string => $expression->attributeCode,
            $this->expressions(),
        )));
    }
}
