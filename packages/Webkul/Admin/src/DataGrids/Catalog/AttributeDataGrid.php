<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeDataGrid extends DataGrid
{
    protected array $typeConfig = [];

    /**
     * Initialize the config value
     */
    public function __construct()
    {
        $this->typeConfig = config('attribute_types');
    }

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $grammar = DB::grammar();

        $queryBuilder = DB::table('attributes')
            ->leftJoin('attribute_translations as attribute_name', function ($join) {
                $join->on('attribute_name.attribute_id', '=', 'attributes.id')
                    ->where('attribute_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->select(
                'attributes.id',
                'attributes.code',
                'type',
                'is_required',
                'is_unique',
                'value_per_locale',
                'value_per_channel',
                'created_at',
                DB::raw(
                    "(CASE 
                        WHEN {$tablePrefix}attribute_name.name IS NULL 
                            OR ".$grammar->length("TRIM({$tablePrefix}attribute_name.name)").' < 1
                        THEN '.$grammar->concat("'['", "{$tablePrefix}attributes.code", "']'")."
                        ELSE {$tablePrefix}attribute_name.name
                    END) AS name"
                )
            );

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
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.type'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => trans($this->typeConfig[$row->type]['name'] ?? "[$row->type]"),
        ]);

        $this->addColumn([
            'index'      => 'is_required',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.required'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_required ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'is_unique',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.unique'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_unique ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'value_per_locale',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.locale-based'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->value_per_locale ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'value_per_channel',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.channel-based'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->value_per_channel ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.catalog.attributes.index.datagrid.created-at'),
            'type'       => 'date_range',
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
        if (bouncer()->hasPermission('catalog.attributes.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.attributes.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.attributes.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('catalog.attributes.mass_delete')) {
            $this->addMassAction([
                'icon'    => 'icon-delete',
                'title'   => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method'  => 'POST',
                'url'     => route('admin.catalog.attributes.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
