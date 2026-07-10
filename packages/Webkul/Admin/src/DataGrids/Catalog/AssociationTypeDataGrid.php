<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AssociationTypeDataGrid extends DataGrid
{
    /**
     * Default sort column
     */
    protected $sortColumn = 'position';

    /**
     * Default sort order
     */
    protected $sortOrder = 'asc';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('association_types')
            ->leftJoin('association_type_translations as requested_association_type_translation', function ($leftJoin) {
                $leftJoin->on('requested_association_type_translation.association_type_id', '=', 'association_types.id')
                    ->where('requested_association_type_translation.locale', core()->getRequestedLocaleCode());
            })
            ->select(
                'association_types.id',
                'code',
                'status',
                'position',
                'is_user_defined',
                'requested_association_type_translation.name as name'
            );

        $this->addFilter('name', 'requested_association_type_translation.name');

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
            'label'      => trans('admin::app.catalog.association_types.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.association_types.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('admin::app.catalog.association_types.index.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type' => 'basic',

                'params' => [
                    'options' => [
                        [
                            'label' => trans('admin::app.catalog.association_types.index.datagrid.activated'),
                            'value' => 1,
                        ], [
                            'label' => trans('admin::app.catalog.association_types.index.datagrid.disabled'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure'    => fn ($row) => $row->status
                ? '<span class="label-active">'.trans('admin::app.catalog.association_types.index.datagrid.activated').'</span>'
                : '<span class="label-info">'.trans('admin::app.catalog.association_types.index.datagrid.disabled').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'position',
            'label'      => trans('admin::app.catalog.association_types.index.datagrid.position'),
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
        if (bouncer()->hasPermission('catalog.association_types.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.association_types.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.association_types.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.association_types.delete')) {
            $this->addAction([
                'icon'      => 'icon-delete',
                'index'     => 'delete',
                'title'     => trans('admin::app.catalog.association_types.index.datagrid.delete'),
                'method'    => 'DELETE',
                'url'       => function ($row) {
                    return route('admin.catalog.association_types.delete', $row->id);
                },
                'condition' => fn ($row) => (bool) $row->is_user_defined,
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
        if (bouncer()->hasPermission('catalog.association_types.mass_delete')) {
            $this->addMassAction([
                'icon'    => 'icon-delete',
                'title'   => trans('admin::app.catalog.association_types.index.datagrid.delete'),
                'method'  => 'POST',
                'url'     => route('admin.catalog.association_types.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }

        if (bouncer()->hasPermission('catalog.association_types.mass_update')) {
            $this->addMassAction([
                'icon'    => 'icon-edit',
                'title'   => trans('admin::app.catalog.association_types.index.datagrid.update-status'),
                'url'     => route('admin.catalog.association_types.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('admin::app.catalog.association_types.index.datagrid.active'),
                        'value' => 1,
                    ], [
                        'label' => trans('admin::app.catalog.association_types.index.datagrid.disable'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
