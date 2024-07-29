<?php

namespace Webkul\Product\Validator;

use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Abstract\ValuesValidator;
use Webkul\Product\Validator\Rule\AttributeValueRule;

class CommonValuesValidator extends ValuesValidator
{
    /**
     * create localeValuesValidator
     */
    public function __construct(
        protected AttributeService $attributeService
    ) {}

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(mixed $data, ?string $productId, array $options)
    {
        $rules = [
            AbstractType::COMMON_VALUES_KEY.'.*' => new AttributeValueRule(attributeService: $this->attributeService, isChannelBased: false, isLocaleBased: false, productId: $productId),
        ];

        return $rules;
    }
}
