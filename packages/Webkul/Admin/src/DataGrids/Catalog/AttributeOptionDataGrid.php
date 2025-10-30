<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeOptionDataGrid extends DataGrid
{
    protected ?int $attributeId;

    /**
     * Get the attribute ID.
     */
    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    /**
     * Set the attribute ID.
     */
    public function setAttributeId(int $attributeId): self
    {
        $this->attributeId = $attributeId;

        return $this;
    }

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $this->attributeId ??= request()->id;

        $queryBuilder = DB::table('attribute_options')
            ->leftJoin('attribute_option_translations as attribute_option_label', function ($join) {
                $join->on('attribute_option_label.attribute_option_id', '=', 'attribute_options.id');
            })
            ->where('attribute_options.attribute_id', $this->attributeId)
            ->select(
                'attribute_options.id',
                'attribute_options.code',
                'attribute_options.swatch_value',
            )
            ->groupBy('attribute_options.id')
            ->orderBy('attribute_options.sort_order', 'asc');

        $locales = core()->getAllActiveLocales()->pluck('code');

        foreach ($locales as $locale) {
            $labelColumn = $tablePrefix.'attribute_option_label.label';
            $localeColumn = $tablePrefix.'attribute_option_label.locale';
            $labelAliasColumn = 'name_'.$locale;

            $queryBuilder->addSelect(DB::raw(
                "MAX(CASE WHEN {$localeColumn} = '{$locale}' THEN $labelColumn END) as {$labelAliasColumn}"
            ));
        }

        $this->addFilter('id', 'attribute_options.id');

        $this->addFilter('code', DB::raw("(SELECT GROUP_CONCAT(CONCAT(attribute_options.code, ' ', label)  SEPARATOR ' ') FROM {$tablePrefix}attribute_option_translations WHERE attribute_option_id = {$tablePrefix}attribute_options.id)"));

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $locales = core()->getAllActiveLocales()->pluck('code');

        $currenctLocaleCode = core()->getCurrentLocale()?->code;

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
        ]);

        foreach ($locales as $locale) {
            $this->addColumn([
                'index'      => 'name_'.$locale,
                'label'      => \Locale::getDisplayName($locale, $currenctLocaleCode),
                'type'       => 'string',
                'searchable' => false,
                'filterable' => false,
                'sortable'   => false,
            ]);
        }
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.attributes.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.options.update', ['attribute_id' => $this->attributeId, 'id' => $row->id]);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.attributes.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.options.delete', ['attribute_id' => $this->attributeId, 'id' => $row->id]);
                },
            ]);
        }
    }
}
