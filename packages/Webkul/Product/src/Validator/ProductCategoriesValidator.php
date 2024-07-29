<?php

namespace Webkul\Product\Validator;

use Webkul\Product\Validator\Abstract\ValuesValidator;

class ProductCategoriesValidator extends ValuesValidator
{
    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options)
    {
        $rules = [
            '*' => 'exists:categories,code',
        ];

        return $rules;
    }

    /**
     * Get validation messages for the validator
     */
    protected function getMessages()
    {
        return [
            '*.exists' => trans('validation.exists-value'),
        ];
    }
}
