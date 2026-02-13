<?php

namespace Webkul\Webhook\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class LogsDataGrid extends DataGrid
{
    /**
     * Search Placeholder
     *
     * @var string
     */
    protected $searchPlaceholder = 'admin::app.components.datagrid.toolbar.search_by.code';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('webhook_logs')->select(
            'id',
            'created_at',
            'sku',
            'user',
            'status'
        );

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId)) {
            $queryBuilder->where('webhook_logs.tenant_id', $tenantId);
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
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.created_at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                $timezone = auth('admin')->user()->timezone ?? config('app.timezone');

                try {
                    $display = \Carbon\Carbon::parse($row->created_at)->setTimezone($timezone)->toDateTimeString();
                } catch (\Exception $e) {
                    $display = $row->created_at;
                }

                return '<span class="icon-calendar"></span> '.$display;
            },
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'user',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.user'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.status'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return $row->status
                    ? '<span class="break-words label-completed">'.trans('webhook::app.configuration.webhook.logs.index.datagrid.success').'</span>'
                    : '<span class="break-words label-canceled">'.trans('webhook::app.configuration.webhook.logs.index.datagrid.failed').'</span>';
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
        if (bouncer()->hasPermission('configuration.webhook.logs.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('webhook::app.configuration.webhook.logs.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('webhook.logs.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare the mass actions
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('configuration.webhook.logs.mass_delete')) {
            $this->addMassAction([
                'title'   => trans('webhook::app.configuration.webhook.logs.index.datagrid.delete'),
                'url'     => route('webhook.logs.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
