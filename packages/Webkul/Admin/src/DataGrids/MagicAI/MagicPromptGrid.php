<?php

namespace Webkul\Admin\DataGrids\MagicAI;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MagicPromptGrid extends DataGrid
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
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('magic_ai_prompts')
            ->select('id', 'prompt', 'title', 'type', 'purpose', 'created_at', 'updated_at');

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
            'label'      => trans('admin::app.configuration.prompt.datagrid.title'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'prompt',
            'label'      => trans('admin::app.configuration.prompt.datagrid.prompt'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.configuration.prompt.datagrid.type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => trans('admin::app.configuration.prompt.datagrid.product'),
                            'value' => 'product',
                        ],
                        [
                            'label' => trans('admin::app.configuration.prompt.datagrid.category'),
                            'value' => 'category',
                        ],
                    ],
                ],
            ],
        ]);

        $this->addColumn([
            'index'      => 'purpose',
            'label'      => trans('admin::app.configuration.prompt.datagrid.purpose'),
            'type'       => 'string',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        ['label' => trans('admin::app.configuration.prompt.datagrid.text-generation'), 'value' => 'text_generation'],
                        ['label' => trans('admin::app.configuration.prompt.datagrid.image-generation'), 'value' => 'image_generation'],
                    ],
                ],
            ],
            'closure' => fn ($row) => match ($row->purpose) {
                'text_generation'  => trans('admin::app.configuration.prompt.datagrid.text-generation'),
                'image_generation' => trans('admin::app.configuration.prompt.datagrid.image-generation'),
                default            => $row->purpose,
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.configuration.prompt.datagrid.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('admin::app.configuration.prompt.datagrid.updated-at'),
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
        if (bouncer()->hasPermission('ai-agent.prompt.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.configuration.prompt.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.magic_ai.prompt.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('ai-agent.prompt.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.configuration.prompt.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.magic_ai.prompt.delete', $row->id),
            ]);
        }
    }
}
