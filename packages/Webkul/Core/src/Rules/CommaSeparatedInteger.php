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
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function isCommaSeparatedInteger($attribute, $value): bool
    {
        $integerValues = explode(',', (string) $value);

        return array_all($integerValues, fn ($integerValue): int|false => preg_match('/^\d+$/', (string) $integerValue));
    }
}
