<?php

namespace Webkul\Admin\DataGrids\Settings\DataTransfer;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ImportDataGrid extends DataGrid
{
    protected $importers;

    /**
     * Intitialize the importers
     */
    public function __construct()
    {
        $this->importers = config('importers');
    }

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
                'file_path',
                'images_directory_path',

            )->where('type', 'import');

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
            'searchable' => true,
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
            'closure'    => function ($row) {
                return isset($this->importers[$row->entity_type]['title']) ? trans($this->importers[$row->entity_type]['title']) : $row->entity_type;
            },
        ]);

        $this->addColumn([
            'index'      => 'action',
            'label'      => trans('admin::app.settings.data-transfer.imports.index.datagrid.action'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $options = [
                    'append' => trans('admin::app.settings.data-transfer.exports.edit.create-update'),
                    'delete' => trans('admin::app.settings.data-transfer.exports.edit.delete'),
                ];

                return isset($options[$row->action]) ? $options[$row->action] : $row->action;
            },
        ]);

        $this->addColumn([
            'index'      => 'file_path',
            'label'      => trans('admin::app.settings.data-transfer.imports.index.datagrid.uploaded-file'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return '<a href="'.route('admin.settings.data_transfer.imports.download', $row->id).'" class="text-violet-700 dark:text-sky-500 hover:underline cursor-pointer">'.$row->file_path.'<a>';
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
        if (bouncer()->hasPermission('settings.data_transfer.imports.import')) {
            $this->addAction([
                'index'  => 'import',
                'icon'   => 'icon-import',
                'title'  => trans('admin::app.settings.data-transfer.imports.index.datagrid.import'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.imports.import-view', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.data_transfer.imports.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.data-transfer.imports.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.imports.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.data_transfer.imports.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.data-transfer.imports.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.imports.delete', $row->id);
                },
            ]);
        }
    }
}
