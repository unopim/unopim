<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Results;

final readonly class ValidationResult
{
    use InteractsWithMessages;

    /**
     * Create a new validation result instance.
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    /**
     * Determine if the validation passed without errors.
     */
    public function passes(): bool
    {
        return ! $this->hasErrors();
    }
}
