<?php

namespace Webkul\ChannelConnector\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ConnectorDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('channel_connectors')
            ->select(
                'channel_connectors.id',
                'channel_connectors.code',
                'channel_connectors.name',
                'channel_connectors.channel_type',
                'channel_connectors.status',
                'channel_connectors.last_synced_at',
                'channel_connectors.created_at',
            );

        $this->addFilter('id', 'channel_connectors.id');
        $this->addFilter('code', 'channel_connectors.code');
        $this->addFilter('name', 'channel_connectors.name');
        $this->addFilter('channel_type', 'channel_connectors.channel_type');
        $this->addFilter('status', 'channel_connectors.status');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('channel_connector::app.connectors.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('channel_connector::app.connectors.datagrid.name'),
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
            'closure'    => fn ($row) => trans("channel_connector::app.connectors.channel-types.{$row->channel_type}"),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('channel_connector::app.connectors.datagrid.status'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => trans("channel_connector::app.connectors.status.{$row->status}"),
        ]);

        $this->addColumn([
            'index'      => 'last_synced_at',
            'label'      => trans('channel_connector::app.connectors.datagrid.last-synced-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('channel_connector.connectors.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('channel_connector::app.acl.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.channel_connector.connectors.edit', $row->code);
                },
            ]);
        }

        if (bouncer()->hasPermission('channel_connector.connectors.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('channel_connector::app.acl.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.channel_connector.connectors.destroy', $row->code);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        // Mass delete removed: no mass delete controller method exists
    }
}
