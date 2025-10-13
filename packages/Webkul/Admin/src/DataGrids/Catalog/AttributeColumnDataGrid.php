<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeColumnDataGrid extends DataGrid
{
    protected $index = 'id';

    protected $sortOrder = 'asc';

    protected $attributeId;

    public function __construct(int $attributeId)
    {
        $this->attributeId = $attributeId;
    }

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('attribute_columns')
            ->leftJoin('attribute_column_translations as act', function ($join) {
                $join->on('act.attribute_column_id', '=', 'attribute_columns.id')
                    ->where('act.locale', core()->getRequestedLocaleCode());
            })
            ->select(
                'attribute_columns.id',
                'attribute_columns.code',
                'attribute_columns.type',
                'attribute_columns.validation',
                'attribute_columns.sort_order',
                DB::raw(
                    "(CASE 
                        WHEN act.label IS NULL OR CHAR_LENGTH(TRIM(act.label)) < 1 
                        THEN CONCAT('[', {$tablePrefix}attribute_columns.code, ']') 
                        ELSE act.label 
                    END) as label"
                )
            )
            ->where('attribute_columns.attribute_id', $this->attributeId);

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.attributes.index.columns.code'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('admin::app.catalog.attributes.index.columns.label'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.catalog.attributes.index.columns.type'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
            'closure'    => fn ($row) => trans('admin::app.catalog.attributes.create.'.$row->type),
        ]);

        $this->addColumn([
            'index'      => 'validation',
            'label'      => trans('admin::app.catalog.attributes.index.columns.validation'),
            'type'       => 'string',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
            'closure'    => fn ($row) => $row->validation
                ? collect(explode(',', $row->validation))
                    ->map(fn ($rule) => trans('admin::app.catalog.attributes.create.'.trim($rule)))
                    ->implode(', ')
                : '',
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.attributes.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.column.get', $row->id);
                },
                'frontend_view'  => 'view-large-modal',
            ]);
        }

        if (bouncer()->hasPermission('catalog.attributes.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.columns.delete', $row->id);
                },
            ]);
        }
    }
}
