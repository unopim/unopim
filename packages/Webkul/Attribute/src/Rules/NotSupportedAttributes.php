<?php

namespace Webkul\Attribute\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotSupportedAttributes implements ValidationRule
{
    const ATTRIBUTE_CODES = [
        'channel',
        'locale',
        'sku',
        'type',
        'parent',
        'attribute_family',
        'configurable_attributes',
        'categories',
        'up_sells',
        'cross_sells',
        'related_products',
        'status',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, self::ATTRIBUTE_CODES)) {
            $fail('core::validation.not-supported')->translate(['unsupported' => implode(', ', self::ATTRIBUTE_CODES)]);
        }
    }
}
