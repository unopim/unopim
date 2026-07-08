<?php

namespace Webkul\Measurement\DataGrids;

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;

/**
 * Extends the core product datagrid so filterable measurement attributes get a
 * dedicated two-field filter (value input + unit dropdown) via the custom
 * 'measurement' column type. The frontend rendering is provided by the
 * overridden datagrid filters view (self-contained child component, so it does
 * NOT collide with the shared price-filter state). Backend matching is handled
 * by the MeasurementFilter.
 */
class MeasurementProductDataGrid extends ProductDataGrid
{
    /**
     * Unit dropdown options per measurement column index.
     *
     * The core Column object only keeps form options for known filter types, so
     * it nulls them for our custom 'measurement' type during formatData(). We
     * cache them here and re-apply them afterwards.
     *
     * @var array<string, array>
     */
    protected array $measurementUnitOptions = [];

    /**
     * {@inheritdoc}
     */
    protected function buildColumnDefinition($attribute)
    {
        $column = parent::buildColumnDefinition($attribute);

        if ($attribute->type === 'measurement') {
            $options = $this->getMeasurementUnitOptions($attribute);

            $column['type'] = 'measurement';
            $column['options'] = $options;

            $this->measurementUnitOptions[$column['index']] = $options;
        }

        return $column;
    }

    /**
     * Re-apply the unit options to measurement columns, which the core Column
     * object discards because 'measurement' is not a recognised filter type.
     *
     * {@inheritdoc}
     */
    public function formatData(): array
    {
        $data = parent::formatData();

        foreach ($data['columns'] as $column) {
            if (
                ($column->type ?? null) === 'measurement'
                && isset($this->measurementUnitOptions[$column->index])
            ) {
                $column->options = $this->measurementUnitOptions[$column->index];
            }
        }

        return $data;
    }

    /**
     * Resolve the operator and value for measurement columns.
     *
     * The custom filter UI applies the value as [[unitCode, amount]], so we
     * unwrap it (like the price filter) and use an exact-match operator.
     *
     * {@inheritdoc}
     */
    protected function getOperatorAndValue($attribute, $value)
    {
        $column = collect($this->columns)->first(fn ($column) => $column->index === $attribute);

        if ($column && ($column->type ?? null) === 'measurement') {
            return [FilterOperators::EQUAL, current($value)];
        }

        return parent::getOperatorAndValue($attribute, $value);
    }

    /**
     * Build the unit dropdown options for a measurement attribute.
     */
    protected function getMeasurementUnitOptions($attribute): array
    {
        $measurement = app(AttributeMeasurementRepository::class)
            ->getByAttributeId($attribute->id);

        if (! $measurement || ! $measurement->family) {
            return [];
        }

        $locale = core()->getRequestedLocaleCode();

        return collect($measurement->family->units ?? [])
            ->map(function ($unit) use ($locale) {
                $label = $unit['labels'][$locale] ?? null;

                if (empty($label)) {
                    $label = ! empty($unit['symbol']) ? $unit['symbol'] : ($unit['code'] ?? '');
                }

                return [
                    'label' => $label,
                    'value' => $unit['code'] ?? '',
                ];
            })
            ->filter(fn ($option) => $option['value'] !== '')
            ->values()
            ->all();
    }
}
