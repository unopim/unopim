<?php

namespace Webkul\Product\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssociationFieldTypes implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, array_keys(config('association_field_types')))) {
            $fail('core::validation.type')->translate();
        }
    }
}
