<?php

namespace Webkul\Admin\DataGrids\MagicAI;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MagicAISystemPromptGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('magic_ai_system_prompts')
            ->select('id', 'title', 'tone', 'max_tokens', 'temperature', 'is_enabled', 'created_at', 'updated_at');

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
            'index'      => 'title',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.title'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'tone',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.tone'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'max_tokens',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.max-tokens'),
            'type'       => 'integer',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'temperature',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.temperature'),
            'type'       => 'float',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'is_enabled',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => trans('admin::app.common.enable'),
                            'value' => 1,
                        ],
                        [
                            'label' => trans('admin::app.common.disable'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => function ($row) {
                return $row->is_enabled
                    ? "<span class='label-active'>".trans('admin::app.common.enable').'</span>'
                    : "<span class='label-info'>".trans('admin::app.common.disable').'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('admin::app.configuration.system-prompt.datagrid.updated-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'title'  => trans('admin::app.configuration.system-prompt.datagrid.edit'),
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.magic_ai.system_prompt.edit', $row->id);
            },
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => trans('admin::app.configuration.system-prompt.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('admin.magic_ai.system_prompt.delete', $row->id);
            },
        ]);
    }
}
