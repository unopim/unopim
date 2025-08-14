<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class LocalesDataGrid extends DataGrid
{
    protected $sortColumn = 'status';

    /**
     * Search Placeholder
     *
     * @var string
     */
    protected $searchPlaceholder = 'admin::app.components.datagrid.toolbar.search_by.code';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('locales')->addSelect('id', 'code', 'status');

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
            'label'      => trans('admin::app.settings.locales.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.settings.locales.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.settings.locales.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return \Locale::getDisplayName($row->code, core()->getCurrentLocale()?->code);
            },
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('admin::app.settings.locales.index.datagrid.status.title'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type' => 'basic',

                'params' => [
                    'options' => [
                        [
                            'label' => trans('admin::app.common.enable'),
                            'value' => 1,
                        ], [
                            'label' => trans('admin::app.common.disable'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure'    => function ($row) {
                return $row->status
                    ? '<span class="label-active">'.trans('admin::app.common.enable').'</span>'
                    : '<span class="label-info">'.trans('admin::app.common.disable').'</span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.locales.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.locales.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.locales.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.locales.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.locales.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.locales.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare the mass actions
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('settings.locales.mass_update')) {
            $this->addMassAction([
                'title'   => trans('admin::app.settings.locales.index.datagrid.mass-update'),
                'url'     => route('admin.settings.locales.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('admin::app.settings.locales.index.datagrid.status.active'),
                        'value' => 1,
                    ], [
                        'label' => trans('admin::app.settings.locales.index.datagrid.status.inactive'),
                        'value' => 0,
                    ],
                ],
            ]);
        }

        if (bouncer()->hasPermission('settings.locales.mass_delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.settings.locales.index.datagrid.delete'),
                'url'     => route('admin.settings.locales.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
