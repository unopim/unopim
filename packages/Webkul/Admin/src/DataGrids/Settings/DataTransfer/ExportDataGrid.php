<?php

namespace Webkul\Admin\DataGrids\Settings\DataTransfer;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ExportDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('job_instances')
            ->addSelect(
                'id',
                'code',
                'entity_type',
                'action',

            )->where('type', 'export');

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
            'label'      => trans('admin::app.settings.data-transfer.imports.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.settings.data-transfer.imports.index.datagrid.code'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'entity_type',
            'label'      => trans('admin::app.settings.data-transfer.imports.index.datagrid.type'),
            'type'       => 'text',
            'searchable' => false,
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
        if (bouncer()->hasPermission('settings.data_transfer.exports.export')) {
            $this->addAction([
                'index'  => 'export',
                'icon'   => 'icon-export',
                'title'  => trans('admin::app.settings.data-transfer.exports.index.datagrid.export'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.exports.export-view', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.data_transfer.exports.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.data-transfer.exports.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.exports.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.data_transfer.exports.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.data-transfer.exports.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.exports.delete', $row->id);
                },
            ]);
        }
    }
}
