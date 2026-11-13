<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Results;

final readonly class ValidationResult
{
    use InteractsWithMessages;

    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public function passes(): bool
    {
        return ! $this->hasErrors();
    }
}
