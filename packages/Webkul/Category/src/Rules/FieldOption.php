<?php

namespace Webkul\Category\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FieldOption implements ValidationRule
{
    /**
     * Constructor.
     *
     * @param  array  $currentIds
     */
    public function __construct(
        protected $field = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $optionCode = is_array($value) ? $value : explode(',', $value);
        $codeNotExists = array_diff($optionCode, $this->getOptionCode());

        if (count($codeNotExists) > 0) {
            $fail(trans('core::validation.field-option-not-found', ['invalid_codes' => implode(',', $codeNotExists)]));
        }
    }

    public function getOptionCode()
    {
        return $this->field->options()->get()->pluck('code')->toArray();
    }
}
