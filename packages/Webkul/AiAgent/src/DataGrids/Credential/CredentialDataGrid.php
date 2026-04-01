<?php

namespace Webkul\AiAgent\DataGrids\Credential;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('ai_agent_credentials')
            ->select('id', 'label', 'provider', 'model', 'status', 'created_at');
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('ai-agent::app.credentials.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('ai-agent::app.credentials.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'provider',
            'label'      => trans('ai-agent::app.credentials.datagrid.provider'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'model',
            'label'      => trans('ai-agent::app.credentials.datagrid.model'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('ai-agent::app.credentials.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->status
                ? '<span class="label-active">'.trans('ai-agent::app.common.yes').'</span>'
                : '<span class="label-info text-gray-600 dark:text-gray-300">'.trans('ai-agent::app.common.no').'</span>',
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'title'  => trans('ai-agent::app.credentials.datagrid.edit'),
            'method' => 'GET',
            'url'    => function ($row) {
                return route('ai-agent.credentials.edit', $row->id);
            },
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => trans('ai-agent::app.credentials.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('ai-agent.credentials.destroy', $row->id);
            },
        ]);
    }
}
