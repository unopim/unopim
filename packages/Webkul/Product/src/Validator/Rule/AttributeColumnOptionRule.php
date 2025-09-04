<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Contracts\AttributeColumn;

class AttributeColumnOptionRule implements ValidationRule
{
    /**
     * Constructor.
     */
    public function __construct(
        protected AttributeColumn $attributeColumn,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $optionCode = is_string($value) ? explode(',', $value) : (is_array($value) ? $value : []);
        $optionCode = array_map('trim', $optionCode);

        $codeNotExists = array_diff($optionCode, $this->getOptionCode($optionCode));

        if (count($codeNotExists) > 0) {
            $fail(trans('core::validation.field-option-not-found', ['invalid_codes' => implode(',', $codeNotExists)]));
        }
    }

    /**
     * find matching options for the input option codes
     */
    public function getOptionCode(array $optionCode): array
    {
        return $this->attributeColumn->options->whereIn('code', $optionCode)->pluck('code')->toArray();
    }
}
