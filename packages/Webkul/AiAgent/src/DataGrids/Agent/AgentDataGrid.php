<?php

namespace Webkul\AiAgent\DataGrids\Agent;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AgentDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('ai_agent_agents as agents')
            ->leftJoin('ai_agent_credentials as creds', 'agents.credentialId', '=', 'creds.id')
            ->select(
                'agents.id',
                'agents.name',
                'agents.status',
                'creds.label as credential',
                'agents.created_at',
            );
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('ai-agent::app.agents.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('ai-agent::app.agents.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'credential',
            'label'      => trans('ai-agent::app.agents.datagrid.credential'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('ai-agent::app.agents.datagrid.status'),
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
            'index'  => 'edit',
            'title'  => trans('ai-agent::app.agents.datagrid.edit'),
            'method' => 'GET',
            'url'    => function ($row) {
                return route('ai-agent.agents.edit', $row->id);
            },
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => trans('ai-agent::app.agents.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('ai-agent.agents.destroy', $row->id);
            },
        ]);
    }
}
