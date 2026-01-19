<?php

namespace Webkul\Attribute\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Enums\SwatchTypeEnum;

class SwatchTypes implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, SwatchTypeEnum::getValues(), true)) {
            $fail(trans('core::validation.type'));
        }
    }
}
