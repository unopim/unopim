<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Sku implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (mb_strlen($value) > 255) {
            $fail('validation.max.string')->translate(['max' => 255]);

            return;
        }

        if ($value !== trim($value)) {
            $fail('core::validation.sku')->translate();

            return;
        }

        if (str_contains($value, ',') || str_contains($value, ';')) {
            $fail('core::validation.sku_delimiter')->translate();
        }
    }
}
