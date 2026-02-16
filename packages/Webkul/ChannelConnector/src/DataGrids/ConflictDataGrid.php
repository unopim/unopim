<?php

namespace Webkul\ChannelConnector\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ConflictDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('channel_sync_conflicts')
            ->leftJoin('products', 'channel_sync_conflicts.product_id', '=', 'products.id')
            ->leftJoin('channel_connectors', 'channel_sync_conflicts.channel_connector_id', '=', 'channel_connectors.id')
            ->select(
                'channel_sync_conflicts.id',
                'products.sku as product_sku',
                'channel_connectors.name as connector_name',
                'channel_sync_conflicts.conflict_type',
                'channel_sync_conflicts.resolution_status',
                'channel_sync_conflicts.created_at',
            );

        $this->addFilter('id', 'channel_sync_conflicts.id');
        $this->addFilter('product_sku', 'products.sku');
        $this->addFilter('connector_name', 'channel_connectors.name');
        $this->addFilter('conflict_type', 'channel_sync_conflicts.conflict_type');
        $this->addFilter('resolution_status', 'channel_sync_conflicts.resolution_status');

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
            'index'      => 'product_sku',
            'label'      => trans('channel_connector::app.conflicts.fields.product'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'connector_name',
            'label'      => trans('channel_connector::app.conflicts.fields.connector'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'conflict_type',
            'label'      => trans('channel_connector::app.conflicts.fields.conflict-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $key = "channel_connector::app.conflicts.conflict-types.{$row->conflict_type}";
                $translated = trans($key);

                return $translated !== $key ? $translated : ucfirst(str_replace('_', ' ', $row->conflict_type));
            },
        ]);

        $this->addColumn([
            'index'      => 'resolution_status',
            'label'      => trans('channel_connector::app.conflicts.fields.resolution-status'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $colors = [
                    'unresolved'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'pim_wins'     => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'channel_wins' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'merged'       => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'dismissed'    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                ];

                $statusClass = $colors[$row->resolution_status] ?? $colors['unresolved'];
                $label = trans("channel_connector::app.conflicts.resolution.{$row->resolution_status}") !== "channel_connector::app.conflicts.resolution.{$row->resolution_status}"
                    ? trans("channel_connector::app.conflicts.resolution.{$row->resolution_status}")
                    : ucfirst(str_replace('_', ' ', $row->resolution_status));

                return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$statusClass.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('channel_connector::app.conflicts.fields.pim-modified-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('channel_connector.conflicts.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('channel_connector::app.acl.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.channel_connector.conflicts.show', $row->id);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        // No mass actions for conflicts
    }
}
