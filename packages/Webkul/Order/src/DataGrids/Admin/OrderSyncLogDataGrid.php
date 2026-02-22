<?php

namespace Webkul\Order\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * Order Sync Log DataGrid
 *
 * Provides listing grid for order synchronization logs
 * with filters and retry actions.
 */
class OrderSyncLogDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'sync_log_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('order_sync_logs')
            ->leftJoin('channels', 'order_sync_logs.channel_id', '=', 'channels.id')
            ->select(
                'order_sync_logs.id as sync_log_id',
                'order_sync_logs.channel_id',
                'channels.name as channel_name',
                'order_sync_logs.status',
                'order_sync_logs.records_synced',
                'order_sync_logs.started_at',
                'order_sync_logs.completed_at',
                'order_sync_logs.error_details',
                'order_sync_logs.created_at'
            );

        $this->addFilter('sync_log_id', 'order_sync_logs.id');
        $this->addFilter('channel_id', 'order_sync_logs.channel_id');
        $this->addFilter('channel_name', 'channels.name');
        $this->addFilter('status', 'order_sync_logs.status');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'sync_log_id',
            'label' => trans('order::app.admin.sync.datagrid.id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('order::app.admin.sync.datagrid.channel'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('order::app.admin.sync.datagrid.status'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $statusLabels = [
                    'pending' => '<span class="badge badge-warning">' . trans('order::app.admin.sync.status.pending') . '</span>',
                    'in_progress' => '<span class="badge badge-info">' . trans('order::app.admin.sync.status.in-progress') . '</span>',
                    'completed' => '<span class="badge badge-success">' . trans('order::app.admin.sync.status.completed') . '</span>',
                    'failed' => '<span class="badge badge-danger">' . trans('order::app.admin.sync.status.failed') . '</span>',
                ];

                return $statusLabels[$row->status] ?? $row->status;
            },
        ]);

        $this->addColumn([
            'index' => 'records_synced',
            'label' => trans('order::app.admin.sync.datagrid.records-synced'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->records_synced ?? 0;
            },
        ]);

        $this->addColumn([
            'index' => 'started_at',
            'label' => trans('order::app.admin.sync.datagrid.started-at'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->started_at ? core()->formatDate($row->started_at, 'Y-m-d H:i:s') : '-';
            },
        ]);

        $this->addColumn([
            'index' => 'completed_at',
            'label' => trans('order::app.admin.sync.datagrid.completed-at'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->completed_at ? core()->formatDate($row->completed_at, 'Y-m-d H:i:s') : '-';
            },
        ]);

        $this->addColumn([
            'index' => 'duration',
            'label' => trans('order::app.admin.sync.datagrid.duration'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if (! $row->started_at || ! $row->completed_at) {
                    return '-';
                }

                $start = new \DateTime($row->started_at);
                $end = new \DateTime($row->completed_at);
                $diff = $start->diff($end);

                return $diff->format('%H:%I:%S');
            },
        ]);

        $this->addColumn([
            'index' => 'error_details',
            'label' => trans('order::app.admin.sync.datagrid.error'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if (! $row->error_details) {
                    return '-';
                }

                return '<span class="text-danger" title="' . e($row->error_details) . '">' .
                       e(substr($row->error_details, 0, 50)) .
                       (strlen($row->error_details) > 50 ? '...' : '') .
                       '</span>';
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
        if (bouncer()->allows('orders.sync.view')) {
            $this->addAction([
                'index' => 'view',
                'icon' => 'icon-eye',
                'title' => trans('order::app.admin.sync.datagrid.view'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.orders.sync.show', $row->sync_log_id);
                },
            ]);
        }

        if (bouncer()->allows('orders.sync.execute')) {
            $this->addAction([
                'index' => 'retry',
                'icon' => 'icon-refresh',
                'title' => trans('order::app.admin.sync.datagrid.retry'),
                'method' => 'POST',
                'url' => function ($row) {
                    return route('admin.orders.sync.retry', $row->sync_log_id);
                },
                'condition' => function ($row) {
                    return $row->status === 'failed';
                },
            ]);
        }
    }
}
