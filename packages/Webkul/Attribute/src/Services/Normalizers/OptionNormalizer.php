<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;

class OptionNormalizer extends AbstractNormalizer implements AttributeNormalizerInterface
{
    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = []): mixed
    {
        return match ($options['format'] ?? 'default') {
            'datagrid' => $this->datagridFormat($data, $attribute, $options),
            default    => $data,
        };
    }

    /**
     * Format data for the datagrid.
     */
    protected function datagridFormat(mixed $data, ?Attribute $attribute, array $options = []): string
    {
        if (in_array($attribute?->type, ['select', 'multiselect'])) {
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
    protected function getOptions(array $data, ?Attribute $attribute, array $options = []): array
    {
        if (! $attribute || empty($data)) {
            return [];
        }

        $locale = $options['locale'] ?? null;

        return $attribute->getOptionsByCodeAndLocale($data, $locale)
            ->map(fn ($option) => $option->label ?? "[{$option->code}]")
            ->all();
    }
}
