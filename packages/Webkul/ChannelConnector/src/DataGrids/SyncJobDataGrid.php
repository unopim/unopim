<?php

namespace Webkul\ChannelConnector\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class SyncJobDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('channel_sync_jobs')
            ->leftJoin('channel_connectors', 'channel_sync_jobs.channel_connector_id', '=', 'channel_connectors.id')
            ->select(
                'channel_sync_jobs.id',
                'channel_connectors.name as connector_name',
                'channel_connectors.channel_type',
                'channel_sync_jobs.sync_type',
                'channel_sync_jobs.status',
                'channel_sync_jobs.total_products',
                'channel_sync_jobs.synced_products',
                'channel_sync_jobs.failed_products',
                'channel_sync_jobs.started_at',
                'channel_sync_jobs.completed_at',
            )
            ->orderBy('channel_sync_jobs.started_at', 'desc');

        $this->addFilter('id', 'channel_sync_jobs.id');
        $this->addFilter('connector_name', 'channel_connectors.name');
        $this->addFilter('channel_type', 'channel_connectors.channel_type');
        $this->addFilter('sync_type', 'channel_sync_jobs.sync_type');
        $this->addFilter('status', 'channel_sync_jobs.status');
        $this->addFilter('started_at', 'channel_sync_jobs.started_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('channel_connector::app.dashboard.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'connector_name',
            'label'      => trans('channel_connector::app.sync.fields.connector'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'channel_type',
            'label'      => trans('channel_connector::app.connectors.datagrid.channel-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $key = "channel_connector::app.connectors.channel-types.{$row->channel_type}";

                return trans()->has($key) ? trans($key) : $row->channel_type;
            },
        ]);

        $this->addColumn([
            'index'      => 'sync_type',
            'label'      => trans('channel_connector::app.sync.fields.sync-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $key = "channel_connector::app.sync.types.{$row->sync_type}";

                return trans()->has($key) ? trans($key) : $row->sync_type;
            },
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('channel_connector::app.sync.fields.status'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $statusClasses = [
                    'completed' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                    'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                    'running'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                    'pending'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                    'retrying'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                ];

                $class = $statusClasses[$row->status] ?? 'bg-gray-100 text-gray-700';
                $label = trans("channel_connector::app.sync.status.{$row->status}");

                return '<span class="rounded px-2 py-0.5 text-xs font-medium '.$class.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'total_products',
            'label'      => trans('channel_connector::app.sync.fields.total-products'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'synced_products',
            'label'      => trans('channel_connector::app.sync.fields.synced-products'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'failed_products',
            'label'      => trans('channel_connector::app.sync.fields.failed-products'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'started_at',
            'label'      => trans('channel_connector::app.sync.fields.started-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'completed_at',
            'label'      => trans('channel_connector::app.sync.fields.completed-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if (! $row->completed_at || ! $row->started_at) {
                    return '-';
                }

                $start = \Carbon\Carbon::parse($row->started_at);
                $end = \Carbon\Carbon::parse($row->completed_at);
                $diff = $start->diff($end);

                if ($diff->h > 0) {
                    return $diff->format('%hh %im %ss');
                }

                if ($diff->i > 0) {
                    return $diff->format('%im %ss');
                }

                return $diff->format('%ss');
            },
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('channel_connector.sync.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('channel_connector::app.acl.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.channel_connector.dashboard.show', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('channel_connector.sync.create')) {
            $this->addAction([
                'icon'   => 'icon-sync',
                'title'  => trans('channel_connector::app.sync.actions.retry-failed'),
                'method' => 'POST',
                'url'    => function ($row) {
                    if ($row->status !== 'failed') {
                        return '';
                    }

                    return route('admin.channel_connector.dashboard.retry', $row->id);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        // No mass actions for sync jobs
    }
}
