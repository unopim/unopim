<?php

namespace Webkul\Product\Services;

use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Contracts\Normalizer as NormalizerContract;
use Webkul\Product\Type\AbstractType;

class AttributeValueNormalizer implements NormalizerContract
{
    public function __construct(
        protected AttributeService $attributeService,
        protected AttributeNormalizerFactory $attributeNormalizerFactory
    ) {}

    /**
     * Normalize the given attribute value.
     */
    public function normalize(mixed $data, array $options = []): ?array
    {
        $channel = $options['channel'] ?? null;
        $locale = $options['locale'] ?? null;

        $values = array_merge(
            $data[AbstractType::COMMON_VALUES_KEY] ?? [],
            $data[AbstractType::LOCALE_VALUES_KEY][$locale] ?? [],
            $data[AbstractType::CHANNEL_VALUES_KEY][$channel] ?? [],
            $data[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channel][$locale] ?? []
        );

        return $this->processNormalizedValues($values, $options);
    }

    public function processNormalizedValues($data, array $options = [])
    {
        $processedOnAttribute = $options['processed_on_attribute'] ?? false;

        return $processedOnAttribute
            ? $this->processedOnAttribute($options['attribute_codes'] ?? [], $data, $options)
            : $this->processedOnRawValues($data, $options);
    }

    public function processedOnRawValues(array $data, array $options = []): array
    {
        $processedData = [];

        foreach ($data as $attributeCode => $value) {

            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            $processedData[$attributeCode] = $value ? $this->getProcessedData($attribute, $value, $options) : $value;
        }

        return $processedData;
    }

    public function processedOnAttribute(array $attributeCodes, array $data, array $options = []): array
    {
        $processedData = [];

        foreach ($attributeCodes as $attributeCode) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);
            if (! $attribute) {
                continue;
            }

            $value = $data[$attribute->code] ?? null;
            $processedData[$attributeCode] = $value ? $this->getProcessedData($attribute, $value, $options) : null;
        }

        return $processedData;
    }

    public function getProcessedData($attribute, $value, $options)
    {
        $normalizer = $this->attributeNormalizerFactory->getNormalizer($attribute->type);

        return $normalizer->normalize($value, $attribute, $options);
    }
}
