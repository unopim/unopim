<?php

namespace Webkul\Category\Services;

class CategoryAdditionalDataMapper
{
    /**
     * Retrieves common fields from the given data array.
     *
     *
     * @return array
     */
    protected function getCommonFields(array $data)
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('additional_data', $data) || ! array_key_exists('common', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['common'];
    }

    /**
     * Retrieves locale-specific fields from the given data array.
     *
     * @param  string  $locale
     * @return array
     */
    protected function getLocaleSpecificFields(array $data, $locale)
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('additional_data', $data) || ! array_key_exists('locale_specific', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['locale_specific'][$locale] ?? [];
    }
}
