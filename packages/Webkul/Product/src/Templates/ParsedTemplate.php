<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class ParsedTemplate
{
    /**
     * Create a new parsed template instance.
     */
    public function __construct(
        public array $segments,
        public array $errors = [],
    ) {}

    /**
     * Determine if parsing the template produced any errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Get the expression segments of the template.
     */
    public function expressions(): array
    {
        return array_values(array_filter(
            $this->segments,
            fn (string|Expression $segment): bool => $segment instanceof Expression,
        ));
    }

    /**
     * Get the unique attribute codes referenced by the template's expressions.
     */
    public function attributeCodes(): array
    {
        return array_values(array_unique(array_map(
            fn (Expression $expression): string => $expression->attributeCode,
            $this->expressions(),
        )));
    }
}
