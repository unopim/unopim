<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeFamilyDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('attribute_families')
            ->leftJoin('attribute_family_translations as attribute_family_name', function ($join) {
                $join->on('attribute_family_name.attribute_family_id', '=', 'attribute_families.id')
                    ->where('attribute_family_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->select(
                'attribute_families.id',
                'attribute_families.code',
                DB::raw('(CASE WHEN '.$tablePrefix.'attribute_family_name.name IS NULL OR CHAR_LENGTH(TRIM('.$tablePrefix.'attribute_family_name.name)) < 1 THEN CONCAT("[", '.$tablePrefix.'attribute_families.code,"]") ELSE '.$tablePrefix.'attribute_family_name.name END) as name')
            );

        $this->addFilter('id', 'attribute_families.id');

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
            'label'      => trans('admin::app.catalog.families.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.families.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.families.index.datagrid.name'),
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
        if (bouncer()->hasPermission('catalog.families.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.families.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.families.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.families.copy')) {
            $this->addAction([
                'method' => 'GET',
                'title'  => trans('admin::app.catalog.families.index.datagrid.copy'),
                'icon'   => 'icon-copy',
                'url'    => function ($row) {
                    return route('admin.catalog.families.copy', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.families.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.families.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.families.delete', $row->id);
                },
            ]);
        }
    }
}
