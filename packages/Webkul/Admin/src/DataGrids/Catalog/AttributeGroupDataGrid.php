<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeGroupDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();
        $grammar = DB::grammar();

        $nameField = "{$tablePrefix}attribute_group_name.name";

        $queryBuilder = DB::table('attribute_groups')
            ->leftJoin('attribute_group_translations as attribute_group_name', function ($join) {
                $join->on('attribute_group_name.attribute_group_id', '=', 'attribute_groups.id')
                    ->where('attribute_group_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->select(
                'attribute_groups.id',
                'attribute_groups.code',
                DB::raw(
                    "(CASE 
                        WHEN $nameField IS NULL 
                            OR ".$grammar->length("TRIM($nameField)")." < 1 
                        THEN ".$grammar->concat("'['", "{$tablePrefix}attribute_groups.code", "']'")."
                        ELSE $nameField 
                    END) as name"
                )
            );

        $this->addFilter('id', 'attribute_groups.id');

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
            'index'      => 'id',
            'label'      => trans('admin::app.catalog.attribute-groups.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.attribute-groups.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.attribute-groups.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.attribute_groups.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.attribute-groups.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.attribute.groups.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.attribute_groups.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attribute-groups.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.attribute.groups.delete', $row->id);
                },
            ]);
        }
    }
}
