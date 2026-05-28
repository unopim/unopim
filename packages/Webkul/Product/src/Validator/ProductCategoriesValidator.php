<?php

namespace Webkul\Product\Validator;

use Webkul\Category\Models\Category;
use Webkul\Product\Validator\Abstract\ValuesValidator;

class ProductCategoriesValidator extends ValuesValidator
{
    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options)
    {
        return [
            '*' => [
                'exists:categories,code',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Single indexed lookup per submitted code — avoids materializing every
                    // root-category code in PHP on each validate call.
                    if (Category::where('code', $value)->whereNull('parent_id')->exists()) {
                        $fail(trans('admin::app.catalog.products.categories.root-not-allowed'));
                    }
                },
            ],
        ];
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
