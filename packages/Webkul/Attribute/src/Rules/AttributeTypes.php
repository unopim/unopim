<?php

namespace Webkul\Attribute\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AttributeTypes implements ValidationRule
{
    const FILE_ATTRIBUTE_TYPE = 'file';

    const IMAGE_ATTRIBUTE_TYPE = 'image';

    const PRICE_ATTRIBUTE_TYPE = 'price';

    const GALLERY_ATTRIBUTE_TYPE = 'gallery';

    const ATTRIBUTE_TYPES = [
        'text',
        'textarea',
        'price',
        'boolean',
        'select',
        'multiselect',
        'datetime',
        'date',
        'image',
        'file',
        'checkbox',
        'gallery',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, self::ATTRIBUTE_TYPES)) {
            $fail('core::validation.type')->translate();
        }
    }
}
