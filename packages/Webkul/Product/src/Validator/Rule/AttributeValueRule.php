<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Contracts\Attribute;

class AttributeValueRule implements ValidationRule
{
    /**
     * create validation rule object
     */
    public function __construct(
        protected $attributeService,
        protected bool $isChannelBased = false,
        protected bool $isLocaleBased = false,
        protected ?string $productId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        ['channel' => $channel, 'locale' => $locale, 'attributeCode' => $attributeCode] = $this->getDataFromAttributeKey($attribute);

        $productAttribute = $this->attributeService->findAttributeByCode($attributeCode);

        if (! $this->isExpectedAttribute($productAttribute, $channel, $locale)) {
            $fail(sprintf('Unexpected Attribute %s', $attributeCode));

            return;
        }

        $rules = [];

        $validations = $productAttribute->getValidationRules(currentChannelCode: $channel, currentLocaleCode: $locale, id: $this->productId);

        if ($productAttribute->type === 'price') {
            $rules[$attributeCode.'.*'] = $validations;
        } else {
            $rules[$attributeCode] = $validations;
        }

        $validator = Validator::make([$attributeCode => $value], $rules);

        if ($validator->fails()) {
            $fail($validator->errors()->first());
        }
    }

    protected function getDataFromAttributeKey(string $attribute)
    {
        $data = explode('.', $attribute);

        if ($this->isChannelBased && $this->isLocaleBased) {
            return [
                'channel'       => $data[1],
                'locale'        => $data[2],
                'attributeCode' => $data[3],
            ];
        }

        if ($this->isChannelBased) {
            return [
                'channel'       => $data[1],
                'locale'        => null,
                'attributeCode' => $data[2],
            ];
        }

        if ($this->isLocaleBased) {
            return [
                'channel'       => null,
                'locale'        => $data[1],
                'attributeCode' => $data[2],
            ];
        }

        return [
            'channel'       => null,
            'locale'        => null,
            'attributeCode' => $data[1],
        ];
    }

    /**
     * Checks if the attribute is expected this section or not
     */
    protected function isExpectedAttribute(?Attribute $attribute, ?string $channel, ?string $locale): bool
    {
        if (! $attribute) {
            return false;
        }

        if ($attribute->isLocaleAndChannelBasedAttribute()) {
            return ! empty($channel) && ! empty($locale);
        }

        if ($attribute->isChannelBasedAttribute()) {
            return ! empty($channel) && empty($locale);
        }

        if ($attribute->isLocaleBasedAttribute()) {
            return ! empty($locale) && empty($channel);
        }

        return empty($channel) && empty($locale);
    }
}
