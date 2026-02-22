<?php

namespace Webkul\Order\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * Webhook DataGrid
 *
 * Provides listing grid for webhook configurations
 * with status toggle and event type display.
 */
class WebhookDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'webhook_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('webhooks')
            ->leftJoin('channels', 'webhooks.channel_id', '=', 'channels.id')
            ->select(
                'webhooks.id as webhook_id',
                'webhooks.name',
                'webhooks.channel_id',
                'channels.name as channel_name',
                'webhooks.endpoint',
                'webhooks.event_types',
                'webhooks.is_active',
                'webhooks.last_triggered_at',
                'webhooks.created_at'
            );

        $this->addFilter('webhook_id', 'webhooks.id');
        $this->addFilter('name', 'webhooks.name');
        $this->addFilter('channel_id', 'webhooks.channel_id');
        $this->addFilter('is_active', 'webhooks.is_active');

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
            'index' => 'name',
            'label' => trans('order::app.admin.webhooks.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('order::app.admin.webhooks.datagrid.channel'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'endpoint',
            'label' => trans('order::app.admin.webhooks.datagrid.endpoint'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                return '<code>' . e(substr($row->endpoint, 0, 50)) .
                       (strlen($row->endpoint) > 50 ? '...' : '') .
                       '</code>';
            },
        ]);

        $this->addColumn([
            'index' => 'event_types',
            'label' => trans('order::app.admin.webhooks.datagrid.events'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                $events = json_decode($row->event_types, true) ?? [];
                $count = count($events);

                if ($count === 0) {
                    return '-';
                }

                $displayed = array_slice($events, 0, 2);
                $html = implode(', ', array_map('e', $displayed));

                if ($count > 2) {
                    $html .= ' <span class="text-muted">+' . ($count - 2) . ' more</span>';
                }

                return $html;
            },
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('order::app.admin.webhooks.datagrid.status'),
            'type' => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-success">' .
                           trans('order::app.admin.webhooks.status.active') .
                           '</span>';
                }

                return '<span class="badge badge-secondary">' .
                       trans('order::app.admin.webhooks.status.inactive') .
                       '</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'last_triggered_at',
            'label' => trans('order::app.admin.webhooks.datagrid.last-triggered'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->last_triggered_at
                    ? core()->formatDate($row->last_triggered_at, 'Y-m-d H:i:s')
                    : trans('order::app.admin.webhooks.never');
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('order::app.admin.webhooks.datagrid.created-at'),
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
        if (bouncer()->allows('orders.webhooks.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('order::app.admin.webhooks.datagrid.edit'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.orders.webhooks.edit', $row->webhook_id);
                },
            ]);

            $this->addAction([
                'index' => 'toggle',
                'icon' => 'icon-power',
                'title' => trans('order::app.admin.webhooks.datagrid.toggle-status'),
                'method' => 'POST',
                'url' => function ($row) {
                    return route('admin.orders.webhooks.toggle-status', $row->webhook_id);
                },
            ]);
        }

        if (bouncer()->allows('orders.webhooks.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('order::app.admin.webhooks.datagrid.delete'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.orders.webhooks.destroy', $row->webhook_id);
                },
            ]);
        }
    }
}
