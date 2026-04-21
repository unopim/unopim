<?php

namespace Webkul\Product\Validator;

use Illuminate\Validation\Rule;
use Webkul\Category\Models\Category;
use Webkul\Product\Validator\Abstract\ValuesValidator;

class ProductCategoriesValidator extends ValuesValidator
{
    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options)
    {
        $rules = [
            '*' => [
                'exists:categories,code',
                Rule::notIn(
                    Category::whereNull('parent_id')->pluck('code')->toArray()
                ),
            ],
        ];

        return $rules;
    }

    /**
     * Get validation messages for the validator
     */
    protected function getMessages()
    {
        return [
            '*.exists'  => trans('validation.exists-value'),
            '*.not_in'  => trans('admin::app.catalog.products.categories.root-not-allowed'),
        ];
    }
}
