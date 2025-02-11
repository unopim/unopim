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
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function normalize($data, array $options = [])
    {
        $channel = $options['channel'] ?? null;
        $locale = $options['locale'] ?? null;

        $values = $data[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channel][$locale] ?? [];

        $values += $data[AbstractType::CHANNEL_VALUES_KEY][$channel] ?? [];

        $values += $data[AbstractType::LOCALE_VALUES_KEY][$locale] ?? [];

        $values += $data[AbstractType::COMMON_VALUES_KEY] ?? [];

        return $this->processNormalizedValues($values, $options);
    }

    public function processNormalizedValues($data, array $options = [])
    {
        $processedOnAttribute = $options['processed_on_attribute'] ?? [];
        $processedData = [];

        foreach ($data as $attributeCode => $value) {
            if (! empty($processedOnAttribute) && ! in_array($attributeCode, $processedOnAttribute)) {
                continue;
            }

            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            $normalizer = $this->attributeNormalizerFactory->getNormalizer($attribute->type);
            $processedValue = $normalizer->normalize($value, $attribute, $options);
            $processedData[$attributeCode] = $processedValue;
        }

        return $processedData;
    }
}
