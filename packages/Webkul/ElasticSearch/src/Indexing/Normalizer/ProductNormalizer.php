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

        return $this->sanitizeArrayKeys($attributeValues);
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
        $attributeValues = $this->sanitizeArrayKeys($attributeValues);

        $attributeCodes = array_values(array_filter(
            array_keys($attributeValues),
            fn ($code) => is_string($code) && trim($code) !== ''
        ));

        $attributes = ! empty($attributeCodes)
            ? $this->attributeService->findByCodes($attributeCodes)
            : [];

        foreach ($attributeValues as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $attribute = $attributes[$key] ?? null;

            if (! $attribute) {
                continue;
            }

            $attributeCode = trim((string) ($attribute['code'] ?? ''));
            $attributeType = trim((string) ($attribute['type'] ?? ''));

            if ($attributeCode === '' || $attributeType === '') {
                continue;
            }

            $attributeValues[$attributeCode.'-'.$attributeType] = $value;

            unset($attributeValues[$key]);
        }

        return $this->sanitizeArrayKeys($attributeValues);
    }

    /**
     * Recursively removes empty-string keys from arrays.
     */
    private function sanitizeArrayKeys(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $resolvedKey = $key;

            if (is_string($key)) {
                $resolvedKey = trim($key);

                if ($resolvedKey === '') {
                    continue;
                }
            }

            if (is_array($value)) {
                $value = $this->sanitizeArrayKeys($value);
            }

            $sanitized[$resolvedKey] = $value;
        }

        return $sanitized;
    }
}
