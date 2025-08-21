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

    const TABLE_ATTRIBUTE_TYPE = 'table';

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
        'table',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, array_keys(config('attribute_types')))) {
            $fail('core::validation.type')->translate();
        }
    }
}
