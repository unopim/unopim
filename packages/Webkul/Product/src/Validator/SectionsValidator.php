<?php

namespace Webkul\Product\Validator;

use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Abstract\ValuesValidator;
use Webkul\Product\Validator\Rule\KeyExistsRule;

class SectionsValidator extends ValuesValidator
{
    const ALLOWED_SECTIONS = [
        AbstractType::CHANNEL_LOCALE_VALUES_KEY,
        AbstractType::LOCALE_VALUES_KEY,
        AbstractType::CHANNEL_VALUES_KEY,
        AbstractType::COMMON_VALUES_KEY,
        AbstractType::CATEGORY_VALUES_KEY,
        AbstractType::ASSOCIATION_VALUES_KEY,
    ];

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options)
    {
        $rules = [
            '*' => new KeyExistsRule(self::ALLOWED_SECTIONS),
        ];

        return $rules;
    }
}
