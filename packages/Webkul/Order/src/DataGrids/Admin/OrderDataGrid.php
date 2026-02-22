<?php

namespace Webkul\Order\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * Order DataGrid
 *
 * Provides listing grid for orders with filters, sorting,
 * and mass actions for admin panel.
 */
class OrderDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'order_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('orders')
            ->leftJoin('channels', 'orders.channel_id', '=', 'channels.id')
            ->select(
                'orders.id as order_id',
                'orders.order_number',
                'orders.channel_id',
                'channels.name as channel_name',
                'orders.customer_email',
                'orders.status',
                'orders.total_amount',
                'orders.currency_code',
                'orders.order_date',
                'orders.created_at'
            )
            ->whereNull('orders.deleted_at');

        $this->addFilter('order_id', 'orders.id');
        $this->addFilter('order_number', 'orders.order_number');
        $this->addFilter('channel_id', 'orders.channel_id');
        $this->addFilter('channel_name', 'channels.name');
        $this->addFilter('customer_email', 'orders.customer_email');
        $this->addFilter('status', 'orders.status');

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
            'index' => 'order_number',
            'label' => trans('order::app.admin.datagrid.order-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('order::app.admin.datagrid.channel'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_email',
            'label' => trans('order::app.admin.datagrid.customer-email'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('order::app.admin.datagrid.status'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $statusLabels = [
                    'pending' => '<span class="badge badge-warning">' . trans('order::app.admin.status.pending') . '</span>',
                    'processing' => '<span class="badge badge-info">' . trans('order::app.admin.status.processing') . '</span>',
                    'completed' => '<span class="badge badge-success">' . trans('order::app.admin.status.completed') . '</span>',
                    'cancelled' => '<span class="badge badge-danger">' . trans('order::app.admin.status.cancelled') . '</span>',
                    'refunded' => '<span class="badge badge-secondary">' . trans('order::app.admin.status.refunded') . '</span>',
                ];

                return $statusLabels[$row->status] ?? $row->status;
            },
        ]);

        $this->addColumn([
            'index' => 'total_amount',
            'label' => trans('order::app.admin.datagrid.total-amount'),
            'type' => 'number',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return ($row->currency_code ?? 'USD') . ' ' . number_format($row->total_amount, 2);
            },
        ]);

        $this->addColumn([
            'index' => 'order_date',
            'label' => trans('order::app.admin.datagrid.order-date'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->order_date);
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('order::app.admin.datagrid.created-at'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->created_at, 'Y-m-d H:i:s');
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
        if (bouncer()->allows('orders.view')) {
            $this->addAction([
                'index' => 'view',
                'icon' => 'icon-eye',
                'title' => trans('order::app.admin.datagrid.view'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.orders.show', $row->order_id);
                },
            ]);
        }

        if (bouncer()->allows('orders.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('order::app.admin.datagrid.edit'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.orders.edit', $row->order_id);
                },
            ]);
        }

        if (bouncer()->allows('orders.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('order::app.admin.datagrid.delete'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.orders.destroy', $row->order_id);
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
        if (bouncer()->allows('orders.edit')) {
            $this->addMassAction([
                'icon' => 'icon-edit',
                'title' => trans('order::app.admin.datagrid.update-status'),
                'method' => 'POST',
                'url' => route('admin.orders.mass-update'),
                'options' => [
                    [
                        'label' => trans('order::app.admin.status.pending'),
                        'value' => 'pending',
                    ],
                    [
                        'label' => trans('order::app.admin.status.processing'),
                        'value' => 'processing',
                    ],
                    [
                        'label' => trans('order::app.admin.status.completed'),
                        'value' => 'completed',
                    ],
                    [
                        'label' => trans('order::app.admin.status.cancelled'),
                        'value' => 'cancelled',
                    ],
                    [
                        'label' => trans('order::app.admin.status.refunded'),
                        'value' => 'refunded',
                    ],
                ],
            ]);
        }

        if (bouncer()->allows('orders.view')) {
            $this->addMassAction([
                'icon' => 'icon-download',
                'title' => trans('order::app.admin.datagrid.export'),
                'method' => 'GET',
                'url' => route('admin.orders.export'),
            ]);
        }
    }
}
