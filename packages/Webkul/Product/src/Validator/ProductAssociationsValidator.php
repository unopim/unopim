<?php

declare(strict_types=1);

namespace Webkul\Product\Validator;

use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Abstract\ValuesValidator;
use Webkul\Product\Validator\Rule\KeyExistsRule;

class ProductAssociationsValidator extends ValuesValidator
{
    const ASSOCIATION_SECTIONS = [
        AbstractType::UP_SELLS_ASSOCIATION_KEY,
        AbstractType::CROSS_SELLS_ASSOCIATION_KEY,
        AbstractType::RELATED_ASSOCIATION_KEY,
    ];

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options): array
    {
        return [
            '*'   => new KeyExistsRule(self::ASSOCIATION_SECTIONS),
            '*.*' => 'exists:products,sku',
        ];
    }

    /**
     * Get validation messages for the validator
     */
    #[\Override]
    protected function getMessages(): array
    {
        return [
            '*.*.exists' => trans('validation.exists-value'),
        ];
    }
}
