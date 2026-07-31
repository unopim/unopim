<?php

namespace Webkul\Product\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssociationNotSupportedFields implements ValidationRule
{
    const FILED_CODES = [
        'code',
        'type',
        'locale',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, self::FILED_CODES)) {
            $fail('core::validation.not-supported')->translate(['unsupported' => implode(', ', self::FILED_CODES)]);
        }
    }
}
