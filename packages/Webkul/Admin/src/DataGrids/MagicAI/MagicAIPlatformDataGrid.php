<?php

namespace Webkul\Admin\DataGrids\MagicAI;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MagicAI\Enums\AiProvider;

class MagicAIPlatformDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        return DB::table('magic_ai_platforms')
            ->select('id', 'label', 'provider', 'models', 'is_default', DB::raw('is_default as is_default_raw'), 'status', 'created_at');
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('admin::app.configuration.platform.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'provider',
            'label'      => trans('admin::app.configuration.platform.datagrid.provider'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(fn ($case) => [
                        'label' => $case->label(),
                        'value' => $case->value,
                    ], AiProvider::cases()),
                ],
            ],
            'closure' => fn ($row) => AiProvider::tryFrom($row->provider)?->label() ?? $row->provider,
        ]);

        $this->addColumn([
            'index'      => 'models',
            'label'      => trans('admin::app.configuration.platform.datagrid.models'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => false,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index'      => 'is_default',
            'label'      => trans('admin::app.configuration.platform.datagrid.default'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        ['label' => trans('admin::app.common.yes'), 'value' => 1],
                        ['label' => trans('admin::app.common.no'), 'value' => 0],
                    ],
                ],
            ],
            'closure' => fn ($row) => $row->is_default
                ? "<span class='label-active'>".trans('admin::app.common.yes').'</span>'
                : "<span class='label-info'>".trans('admin::app.common.no').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('admin::app.configuration.platform.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        ['label' => trans('admin::app.common.enable'), 'value' => 1],
                        ['label' => trans('admin::app.common.disable'), 'value' => 0],
                    ],
                ],
            ],
            'closure' => fn ($row) => $row->status
                ? "<span class='label-active'>".trans('admin::app.common.enable').'</span>'
                : "<span class='label-info'>".trans('admin::app.common.disable').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.configuration.platform.datagrid.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => true,
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('ai-agent.platform.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.configuration.platform.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.magic_ai.platform.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('ai-agent.platform.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.configuration.platform.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.magic_ai.platform.delete', $row->id),
            ]);
        }
    }
}
