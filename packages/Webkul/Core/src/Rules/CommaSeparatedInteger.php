<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CommaSeparatedInteger implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isCommaSeparatedInteger($attribute, $value)) {
            $fail('core::validation.comma-separated-integer')->translate();
        }
    }

    /**
     * Determine if the value is comma separated integer.
     */
    public function isCommaSeparatedInteger(string $attribute, mixed $value): bool
    {
        $integerValues = explode(',', (string) $value);

        foreach ($integerValues as $integerValue) {
            if (! preg_match('/^\d+$/', $integerValue)) {
                return false;
            }
        }

        return true;
    }
}
