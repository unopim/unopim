<?php

namespace Webkul\Product\Services;

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
            || ! array_key_exists('common', $data['values'])
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
            || ! array_key_exists('locale_specific', $data['values'])
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
            || ! array_key_exists('channel_specific', $data['values'])
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
            || ! array_key_exists('channel_locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_locale_specific'][$channel][$locale] ?? [];
    }

    /**
     * Retrieves and formats the categories associated with a product.
     *
     *
     * @return string|null
     */
    protected function getCategories(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('categories', $data['values'])
            || ! is_array($data['values']['categories'])
        ) {
            return;
        }

        return implode(',', $data['values']['categories']);
    }

    /**
     * Retrieves and formats the associated products for a given data row and type.
     *
     *
     * @return string|null
     */
    protected function getAssociations(array $data, string $type)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('associations', $data['values'])
            || ! is_array($data['values']['associations'])
            || ! array_key_exists($type, $data['values']['associations'])
        ) {
            return;
        }

        return implode(',', $data['values']['associations'][$type]) ?? null;
    }
}
