<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\Attribute as AttributeModel;

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
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        ['channel' => $channel, 'locale' => $locale, 'attributeCode' => $attributeCode] = $this->getDataFromAttributeKey($attribute);

        $productAttribute = $this->attributeService->findAttributeByCode($attributeCode);

        if (! $this->isExpectedAttribute($productAttribute, $channel, $locale)) {
            $fail(sprintf('Unexpected Attribute %s', $attributeCode));

            return;
        }

        $validations = $productAttribute->getValidationRules(currentChannelCode: $channel, currentLocaleCode: $locale, id: $this->productId);

        $ruleKey = $this->ruleKey($productAttribute, $value);

        $validator = Validator::make(
            [$attributeCode => $value],
            [$ruleKey => $validations],
            [],
            $this->customAttributeNames($productAttribute, $ruleKey)
        );

        if ($validator->fails()) {
            $fail($validator->errors()->first());
        }
    }

    /**
     * Rule target for the submitted value.
     *
     * A composite attribute posts a keyed payload — every currency of a price, the
     * amount and unit of a measurement — so the field rules have to address the
     * scalar member. Aiming them at the payload hands an array to rules such as
     * Decimal, which then rejects a field the user never filled. The stored form of
     * a measurement keys the amount as `amount`, the submitted form as `value`.
     */
    protected function ruleKey(Attribute $attribute, mixed $value): string
    {
        if ($attribute->type === AttributeModel::PRICE_FIELD_TYPE) {
            return $attribute->code.'.*';
        }

        if (
            $attribute->type === AttributeModel::MEASUREMENT_FIELD_TYPE
            && is_array($value)
        ) {
            return $attribute->code.'.'.(array_key_exists('amount', $value) ? 'amount' : 'value');
        }

        return $attribute->code;
    }

    /**
     * A measurement rule targets the amount member, so without a label the message
     * would name the payload key. Price keeps its expanded key, which carries the
     * currency that actually failed.
     *
     * @return array<string, string>
     */
    protected function customAttributeNames(Attribute $attribute, string $ruleKey): array
    {
        if ($attribute->type !== AttributeModel::MEASUREMENT_FIELD_TYPE) {
            return [];
        }

        return [$ruleKey => $attribute->name ?: $attribute->code];
    }

    /**
     * @return string[]|null[]
     */
    protected function getDataFromAttributeKey(string $attribute): array
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
        if (! $attribute instanceof Attribute) {
            return false;
        }

        if ($attribute->isLocaleAndChannelBasedAttribute()) {
            return ! in_array($channel, [null, '', '0'], true) && ! in_array($locale, [null, '', '0'], true);
        }

        if ($attribute->isChannelBasedAttribute()) {
            return ! in_array($channel, [null, '', '0'], true) && in_array($locale, [null, '', '0'], true);
        }

        if ($attribute->isLocaleBasedAttribute()) {
            return ! in_array($locale, [null, '', '0'], true) && in_array($channel, [null, '', '0'], true);
        }

        return in_array($channel, [null, '', '0'], true) && in_array($locale, [null, '', '0'], true);
    }
}
