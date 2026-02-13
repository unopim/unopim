<?php

namespace Webkul\Admin\DataGrids\Settings\DataTransfer;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\DataTransfer\Helpers\Import;

class JobTrackerGrid extends DataGrid
{
    protected $importers;

    /**
     * Initialize the importers
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
        $queryBuilder = DB::table('job_track')
            ->leftJoin('job_instances as job', 'job.id', '=', 'job_track.job_instances_id')
            ->leftJoin('admins', 'admins.id', '=', 'job_track.user_id')
            ->addSelect(
                'job_track.id',
                'job.code as job_code',
                'job.entity_type',
                'job.type',
                'state',
                'status',
                'processed_rows_count',
                'invalid_rows_count',
                'started_at',
                'completed_at',
                'job_track.user_id',
                'admins.name as user',
                'job_track.created_at',
                'job_track.updated_at',
            );

        $this->addFilter('id', 'job_track.id');
        $this->addFilter('job_code', 'job.code');
        $this->addFilter('type', 'job.type');

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId)) {
            $queryBuilder->where('job_track.tenant_id', $tenantId);
        }

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
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'job_code',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.job_code'),
            'type'       => 'text',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'entity_type',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.type'),
            'type'       => 'text',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return isset($this->importers[$row->entity_type]['title']) ? trans($this->importers[$row->entity_type]['title']) : $row->entity_type;
            },
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.job_type'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'state',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.state'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                switch ($row->state) {
                    case Import::STATE_PENDING:
                        return '<p class="label-pending">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.pending').'</p>';

                    case Import::STATE_VALIDATED:
                        return '<p class="label-active">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.validated').'</p>';

                    case Import::STATE_PROCESSING:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.processing').'</p>';

                    case Import::STATE_PROCESSED:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.processed').'</p>';

                    case Import::STATE_LINKING:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.linking').'</p>';

                    case Import::STATE_LINKED:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.linked').'</p>';

                    case Import::STATE_INDEXING:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.indexing').'</p>';

                    case Import::STATE_INDEXED:
                        return '<p class="label-processing">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.indexed').'</p>';

                    case Import::STATE_COMPLETED:
                        return '<p class="label-completed">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.completed').'</p>';

                    case Import::STATE_FAILED:
                        return '<p class="label-info">'.trans('admin::app.settings.data-transfer.tracker.index.datagrid.failed').'</p>';
                }
            },
        ]);

        $this->addColumn([
            'index'      => 'user',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.user'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'started_at',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.started_at'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'completed_at',
            'label'      => trans('admin::app.settings.data-transfer.tracker.index.datagrid.completed_at'),
            'type'       => 'text',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('data_transfer.job_tracker')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-view',
                'title'  => trans('admin::app.settings.data-transfer.imports.index.datagrid.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.data_transfer.tracker.view', $row->id);
                },
            ]);
        }
    }
}
