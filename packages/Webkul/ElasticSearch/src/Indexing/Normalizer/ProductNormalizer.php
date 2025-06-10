<?php

namespace Webkul\ElasticSearch\Indexing\Normalizer;

use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Type\AbstractType;

class ProductNormalizer
{
    public function __construct(
        protected AttributeService $attributeService
    ) {}

    /**
     * Normalizes the given attribute value for elasticsearch indexing format.
     */
    public function normalize(array $attributeValues, array $options = []): array
    {
        if (! empty($attributeValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY])) {
            foreach ($attributeValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY] as $channel => $localeValues) {
                foreach ($localeValues as $locale => $values) {
                    $attributeValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channel][$locale] = $this->normalizeAttributeKey($values);
                }
            }
        }

        if (! empty($attributeValues[AbstractType::CHANNEL_VALUES_KEY])) {
            foreach ($attributeValues[AbstractType::CHANNEL_VALUES_KEY] as $key => $values) {
                $attributeValues[AbstractType::CHANNEL_VALUES_KEY][$key] = $this->normalizeAttributeKey($values);
            }
        }

        if (! empty($attributeValues[AbstractType::LOCALE_VALUES_KEY])) {
            foreach ($attributeValues[AbstractType::LOCALE_VALUES_KEY] as $key => $values) {
                $attributeValues[AbstractType::LOCALE_VALUES_KEY][$key] = $this->normalizeAttributeKey($values);
            }
        }

        if (! empty($attributeValues[AbstractType::COMMON_VALUES_KEY])) {
            $attributeValues[AbstractType::COMMON_VALUES_KEY] = $this->normalizeAttributeKey($attributeValues[AbstractType::COMMON_VALUES_KEY]);
        }

        return $attributeValues;
    }

    /**
     * Processes attribute key to include attribute type for mapping to correct elasticsearch field
     *
     * Example Input:
     * [
     *     'name' => 'example',
     * ]
     *
     * Example Output:
     * [
     *     'name-text' => 'example',
     * ]
     */
    public function normalizeAttributeKey(array $attributeValues): array
    {
        $attributes = $this->attributeService->findByCodes(array_keys($attributeValues));

        foreach ($attributeValues as $key => $value) {
            $attribute = $attributes[$key] ?? null;

            if (! $attribute) {
                continue;
            }

            $attributeValues[$key.'-'.$attribute['type']] = $value;

            unset($attributeValues[$key]);
        }

        return $attributeValues;
    }
}
