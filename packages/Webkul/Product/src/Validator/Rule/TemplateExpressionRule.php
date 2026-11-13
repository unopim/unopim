<?php

declare(strict_types=1);

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Contracts\TemplateValidator;

class TemplateExpressionRule implements ValidationRule
{
    public function __construct(protected Attribute $attribute) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '{{')) {
            return;
        }

        foreach (resolve(TemplateValidator::class)->validate($value, $this->attribute)->errorMessages() as $message) {
            $fail($message);
        }
    }
}
