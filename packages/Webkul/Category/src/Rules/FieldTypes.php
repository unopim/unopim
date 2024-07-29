<?php

namespace Webkul\Category\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FieldTypes implements ValidationRule
{
    const FILED_TYPES = [
        'text',
        'textarea',
        'boolean',
        'select',
        'multiselect',
        'datetime',
        'date',
        'image',
        'file',
        'checkbox',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, self::FILED_TYPES)) {
            $fail('core::validation.type')->translate();
        }
    }
}
