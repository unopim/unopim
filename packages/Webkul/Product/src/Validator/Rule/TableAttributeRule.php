<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Contracts\Attribute;

class TableAttributeRule implements ValidationRule
{
    /**
     * create validation rule object
     */
    public function __construct(
        protected Attribute $tableAttribute
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->tableAttribute->columns()->get() as $column) {
            $rules = [];

            $rules[$column->code] = $column->getValidationRules();
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        foreach ($value as $row) {
            $validator = Validator::make($row, $rules);

            if ($validator->fails()) {
                $fail($validator->errors()->first());
            }
        }
    }
}
