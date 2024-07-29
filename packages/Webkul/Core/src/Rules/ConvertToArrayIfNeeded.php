<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConvertToArrayIfNeeded implements ValidationRule
{
    // /**
    //  * Get the validation error message.
    //  *
    //  * @return string
    //  */
    // public function message()
    // {
    //     return 'The :attribute must be a single value or a comma-separated list.';
    // }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isCommaSeparatedString($attribute, $value)) {
            $fail('core::validation.comma-separated-string')->translate();
        }
    }

    /**
     * Determine if the value is comma separated integer.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function isCommaSeparatedString($attribute, $value)
    {

        if (strpos($value, ',') !== false) {
            // Convert to array
            request()->merge([$attribute => explode(',', $value)]);
        }

        return true;
    }
}
