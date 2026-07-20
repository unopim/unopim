<?php

namespace Webkul\Webhook\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WebhookDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('webhooks')->select(
            'id',
            'name',
            'url',
            'is_active',
            'events',
            'created_at'
        );
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('webhook::app.webhooks.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('webhook::app.webhooks.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'url',
            'label'      => trans('webhook::app.webhooks.index.datagrid.url'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'events',
            'label'      => trans('webhook::app.webhooks.index.datagrid.events'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row): string {
                $events = is_string($row->events ?? null) ? json_decode($row->events, true) : ($row->events ?? []);

                return '<span class="badge badge-md badge-info">'.count((array) $events).'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('webhook::app.webhooks.index.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => trans('webhook::app.webhooks.index.datagrid.active'),
                            'value' => 1,
                        ],
                        [
                            'label' => trans('webhook::app.webhooks.index.datagrid.inactive'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => function ($row): string {
                if ($row->is_active) {
                    return '<span class="label-active">'.trans('webhook::app.webhooks.index.datagrid.active').'</span>';
                }

                return '<span class="label-canceled">'.trans('webhook::app.webhooks.index.datagrid.inactive').'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('webhook::app.webhooks.index.datagrid.created_at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('configuration.webhook.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('webhook::app.webhooks.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('webhook.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('configuration.webhook.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('webhook::app.webhooks.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('webhook.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('configuration.webhook.delete')) {
            $this->addMassAction([
                'title'   => trans('webhook::app.webhooks.index.datagrid.delete'),
                'url'     => route('webhook.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
