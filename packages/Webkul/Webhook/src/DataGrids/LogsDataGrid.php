<?php

namespace Webkul\Webhook\DataGrids;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
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
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('webhook_logs')->select(
            'id',
            'created_at',
            'sku',
            'user',
            'status',
            'extra'
        );

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
                    $display = Carbon::parse($row->created_at)->setTimezone($timezone)->toDateTimeString();
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
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => trans('webhook::app.configuration.webhook.logs.index.datagrid.success'),
                            'value' => 1,
                        ],
                        [
                            'label' => trans('webhook::app.configuration.webhook.logs.index.datagrid.failed'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => function ($row) {
                $extra = is_string($row->extra ?? null) ? json_decode($row->extra, true) : ($row->extra ?? []);
                $code = $extra['response']['status'] ?? null;

                if ($row->status) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.success');

                    if (is_numeric($code) && (int) $code > 0) {
                        $label .= ' ('.(int) $code.')';
                    }

                    return '<span class="break-words label-completed">'.$label.'</span>';
                }

                if (! is_numeric($code) || (int) $code === 0) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.timeout_or_error');
                } elseif ((int) $code >= 500) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.server_error').' ('.(int) $code.')';
                } else {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.failed').' ('.(int) $code.')';
                }

                return '<span class="break-words label-canceled">'.$label.'</span>';
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
