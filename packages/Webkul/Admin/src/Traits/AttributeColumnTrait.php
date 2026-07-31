<?php

namespace Webkul\Admin\Traits;

use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Filters\ProductFilterOperators;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

trait AttributeColumnTrait
{
    /**
     * Build column definition based on attribute properties.
     *
     * @param  object  $attribute
     * @return array
     */
    protected function buildColumnDefinition($attribute)
    {
        $attributeArray = $attribute->toArray();

        $label = $attribute->getTranslatedValueWithFallback('name');

        $attributeType = is_array($attributeArray) && isset($attributeArray['type']) && is_string($attributeArray['type'])
            ? $attributeArray['type']
            : null;

        $column = [
            'index'          => $attributeArray['code'],
            'label'          => ! empty($label) ? $label : '['.$attributeArray['code'].']',
            'type'           => $attribute->getFilterType(),
            'searchable'     => false,
            'filterable'     => $attributeArray['is_filterable'] ?? false,
            'sortable'       => true,
            'attribute_type' => $attributeType,
            'operators'      => ProductFilterOperators::optionsForType($attributeType),

            'removable_filter' => true,
        ];

        return $this->applyFilterTypeOptions($column, $attribute);
    }

    /**
     * Apply specific filter type options to the column.
     *
     * @param  object  $attribute
     * @return array
     */
    protected function applyFilterTypeOptions(array $column, $attribute)
    {
        switch ($column['type']) {
            case 'boolean':
                $column['options'] = $this->getBooleanOptions();
                break;
            case 'price':
                $column['options'] = $attribute->type === Attribute::MEASUREMENT_FIELD_TYPE
                    ? $this->getMeasurementUnitOptions($attribute)
                    : $this->getPriceOptions();
                break;
            case 'image':
                $column['closure'] = $this->getImageClosure();
                break;
            case 'gallery':
                $column['closure'] = $this->getGalleryClosure();
                break;
            case 'dropdown':
                $column['options'] = $this->getDropdownOptions($attribute);
                break;
        }

        if ($attribute->type === Attribute::FILE_ATTRIBUTE_TYPE) {
            $column['closure'] = $this->getFileClosure();
        }

        return $column;
    }

    protected function getBooleanOptions()
    {
        return [
            ['label' => trans('admin::app.common.enable'), 'value' => true],
            ['label' => trans('admin::app.common.disable'), 'value' => false],
        ];
    }

    protected function getPriceOptions()
    {
        return array_map(fn ($currency) => [
            'label' => $currency->name ?: '['.$currency->code.']',
            'value' => $currency->code,
        ], core()->getAllActiveCurrencies()->all());
    }

    /**
     * Unit dropdown options for a measurement attribute. A measurement column
     * reuses the price filter layout, so the leading dropdown lists the units of
     * the attribute's measurement family instead of currencies.
     *
     * @param  object  $attribute
     */
    protected function getMeasurementUnitOptions($attribute): array
    {
        if (! class_exists(AttributeMeasurementRepository::class)) {
            return [];
        }

        $measurement = resolve(AttributeMeasurementRepository::class)->getByAttributeId($attribute->id);

        if (! $measurement || ! $measurement->family) {
            return [];
        }

        $locale = core()->getRequestedLocaleCode();

        return collect($measurement->family->units ?? [])
            ->map(function ($unit) use ($locale): array {
                $label = $unit['labels'][$locale] ?? null;

                if (empty($label)) {
                    $label = empty($unit['symbol']) ? $unit['code'] ?? '' : $unit['symbol'];
                }

                return [
                    'label' => $label,
                    'value' => $unit['code'] ?? '',
                ];
            })
            ->filter(fn ($option): bool => $option['value'] !== '')
            ->values()
            ->all();
    }

    protected function getImageClosure()
    {
        return fn ($value) => ! empty($value) ? Storage::url(is_array($value) ? $value[0] : $value) : '';
    }

    protected function getFileClosure()
    {
        return fn ($value) => ! empty($value) ? e(basename(is_array($value) ? $value[0] : $value)) : '';
    }

    protected function getGalleryClosure()
    {
        return function ($value) {
            if (empty($value)) {
                return '';
            }

            $first = is_array($value) ? $value[0] : $value;

            try {
                $mime = Storage::mimeType($first) ?: '';
            } catch (\Exception $e) {
                $mime = '';
            }

            if (str_starts_with($mime, 'video/')) {
                return [
                    'type' => 'video',
                    'url'  => Storage::url($first),
                ];
            }

            return [
                'type' => 'image',
                'url'  => Storage::url($first),
            ];
        };
    }

    protected function getDropdownOptions($attribute)
    {
        return [
            'type'   => 'sync',
            'route'  => route('admin.catalog.options.fetch-all'),
            'params' => [
                'attributeId' => $attribute->id,
                'entityName'  => 'attribute',
            ],
        ];
    }
}
