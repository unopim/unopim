<?php

declare(strict_types=1);

namespace Webkul\Category\Services;

class CategoryAdditionalDataMapper
{
    /**
     * Retrieves common fields from the given data array.
     */
    public function getCommonFields(array $data): array
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('common', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['common'];
    }

    /**
     * Retrieves locale-specific fields from the given data array.
     */
    public function getLocaleSpecificFields(array $data, string $locale): array
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('locale_specific', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['locale_specific'][$locale] ?? [];
    }
}
