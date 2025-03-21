<?php

namespace Webkul\Admin\Traits;

use Illuminate\Support\Facades\Storage;

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
        $column = [
            'index'      => $attribute->code,
            'label'      => $attribute->name ?: '['.$attribute->code.']',
            'type'       => $attribute->getFilterType(),
            'searchable' => false,
            'filterable' => $attribute->is_filterable ?? false,
            'sortable'   => true,
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
                $column['options'] = $this->getPriceOptions();
                break;
            case 'image':
                $column['closure'] = $this->getImageClosure();
                break;
            case 'dropdown':
                $column['options'] = $this->getDropdownOptions($attribute);
                break;
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

    protected function getImageClosure()
    {
        return fn ($value) => ! empty($value) ? Storage::url(is_array($value) ? $value[0] : $value) : '';
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
