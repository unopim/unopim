<?php

namespace Webkul\Product\Services;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Type\AbstractType;

class ProductValueMapper
{
    /**
     * Retrieves and formats the common fields for a product.
     *
     * @return array
     */
    public function getCommonFields(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('common', $data['values'] ?? [])
        ) {
            return [];
        }

        return $data['values']['common'];
    }

    /**
     * Retrieves and formats the locale-specific fields for a product.
     *
     * @return array
     */
    public function getLocaleSpecificFields(array $data, string $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('locale_specific', $data['values'] ?? [])
        ) {
            return [];
        }

        return $data['values']['locale_specific'][$locale] ?? [];
    }

    /**
     * Retrieves and formats the channel-specific fields for a product.
     *
     * @return array
     */
    public function getChannelSpecificFields(array $data, string $channel)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_specific', $data['values'] ?? [])
        ) {
            return [];
        }

        return $data['values']['channel_specific'][$channel] ?? [];
    }

    /**
     * Retrieves and formats the channel-locale-specific fields for a product.
     *
     * @return array
     */
    public function getChannelLocaleSpecificFields(array $data, string $channel, string $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_locale_specific', $data['values'] ?? [])
        ) {
            return [];
        }

        return $data['values']['channel_locale_specific'][$channel][$locale] ?? [];
    }

    /**
     * Retrieves every field visible in the given scope, with the more specific buckets winning.
     *
     * @return array<string, mixed>
     */
    public function getScopedFields(array $data, string $channel, string $locale): array
    {
        return array_merge(
            $this->getCommonFields($data),
            $this->getLocaleSpecificFields($data, $locale),
            $this->getChannelSpecificFields($data, $channel),
            $this->getChannelLocaleSpecificFields($data, $channel, $locale)
        );
    }

    /**
     * Path inside the product `values` array where the attribute is stored for the given scope.
     *
     * @return list<string>
     */
    public function getScopePath(Attribute $attribute, string $channel, string $locale): array
    {
        return match (true) {
            (bool) $attribute->value_per_channel && (bool) $attribute->value_per_locale => [AbstractType::CHANNEL_LOCALE_VALUES_KEY, $channel, $locale],
            (bool) $attribute->value_per_channel                                        => [AbstractType::CHANNEL_VALUES_KEY, $channel],
            (bool) $attribute->value_per_locale                                         => [AbstractType::LOCALE_VALUES_KEY, $locale],
            default                                                                     => [AbstractType::COMMON_VALUES_KEY],
        };
    }

    /**
     * Reads the attribute value from the bucket its scope dictates.
     *
     * @param  array<string, mixed>  $values  the product `values` array
     */
    public function getScopedValue(array $values, Attribute $attribute, string $channel, string $locale): mixed
    {
        $bucket = $values;

        foreach ($this->getScopePath($attribute, $channel, $locale) as $segment) {
            if (! is_array($bucket) || ! array_key_exists($segment, $bucket)) {
                return null;
            }

            $bucket = $bucket[$segment];
        }

        return is_array($bucket) ? ($bucket[$attribute->code] ?? null) : null;
    }

    /**
     * Writes the attribute value into the bucket its scope dictates.
     *
     * @param  array<string, mixed>  $values  the product `values` array
     * @return array<string, mixed>
     */
    public function setScopedValue(array $values, Attribute $attribute, string $channel, string $locale, mixed $value): array
    {
        $bucket = &$values;

        foreach ($this->getScopePath($attribute, $channel, $locale) as $segment) {
            if (! isset($bucket[$segment]) || ! is_array($bucket[$segment])) {
                $bucket[$segment] = [];
            }

            $bucket = &$bucket[$segment];
        }

        $bucket[$attribute->code] = $value;

        unset($bucket);

        return $values;
    }

    /**
     * Retrieves and formats the categories associated with a product.
     */
    public function getCategories(array $data): ?string
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('categories', $data['values'] ?? [])
            || ! is_array($data['values']['categories'])
        ) {
            return null;
        }

        return implode(',', $data['values']['categories']);
    }

    /**
     * Retrieves and formats the associated products for a given data row and type.
     */
    public function getAssociations(array $data, string $type): ?string
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('associations', $data['values'] ?? [])
            || ! is_array($data['values']['associations'])
            || ! array_key_exists($type, $data['values']['associations'])
        ) {
            return null;
        }

        return implode(',', $data['values']['associations'][$type]) ?? null;
    }
}
