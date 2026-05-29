<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Database\Query\Builder;
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
     */
    public function prepareQueryBuilder(): Builder
    {
        return DB::table('locales')->addSelect('id', 'code', 'status');
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
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
            'closure'    => fn (\stdClass $row) => \Locale::getDisplayName($row->code, core()->getCurrentLocale()?->code),
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
            'closure'    => fn (\stdClass $row) => $row->status
                ? '<span class="label-active">'.trans('admin::app.common.enable').'</span>'
                : '<span class="label-info">'.trans('admin::app.common.disable').'</span>',
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('settings.locales.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.locales.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn (\stdClass $row) => route('admin.settings.locales.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('settings.locales.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.locales.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn (\stdClass $row) => route('admin.settings.locales.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare the mass actions
     */
    public function prepareMassActions(): void
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
