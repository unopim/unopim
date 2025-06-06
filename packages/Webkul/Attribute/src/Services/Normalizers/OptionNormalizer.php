<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;
use Webkul\Attribute\Models\Attribute;

class OptionNormalizer extends AbstractNormalizer implements AttributeNormalizerInterface
{
    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?AttributeContract $attribute = null, array $options = []): mixed
    {
        return match ($options['format'] ?? 'default') {
            'datagrid' => $this->datagridFormat($data, $attribute, $options),
            default    => $data,
        };
    }

    /**
     * Format data for the datagrid.
     */
    protected function datagridFormat(mixed $data, ?AttributeContract $attribute, array $options = []): string
    {
        if (in_array($attribute?->type, [Attribute::SELECT_FIELD_TYPE, Attribute::MULTISELECT_FIELD_TYPE, Attribute::CHECKBOX_FIELD_TYPE])) {
            return implode(',', $this->getOptions(
                is_array($data) ? $data : explode(',', $data),
                $attribute,
                $options
            ));
        }

        return (string) $data;
    }

    /**
     * Retrieve options based on attribute and locale.
     */
    protected function getOptions(array $data, ?AttributeContract $attribute, array $options = []): array
    {
        if (! $attribute || empty($data)) {
            return [];
        }

        $locale = $options['locale'] ?? null;

        $options = $attribute->options()->whereIn('code', $data)->get();

        $formattedOptions = [];

        foreach ($options as $option) {
            $translatedLabel = $option->translations()?->where('locale', $locale)?->first()?->label;
            $formattedOptions[] = empty($translatedLabel) ? "[{$option->code}]" : $translatedLabel;
        }

        return $formattedOptions;
    }
}
