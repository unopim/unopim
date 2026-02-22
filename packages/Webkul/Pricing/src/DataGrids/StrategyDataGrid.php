<?php

namespace Webkul\Pricing\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class StrategyDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('pricing_strategies')
            ->select(
                'pricing_strategies.id',
                'pricing_strategies.scope_type',
                'pricing_strategies.scope_id',
                'pricing_strategies.minimum_margin_percentage',
                'pricing_strategies.target_margin_percentage',
                'pricing_strategies.premium_margin_percentage',
                'pricing_strategies.is_active',
                'pricing_strategies.priority',
                'pricing_strategies.created_at',
            );

        $this->addFilter('id', 'pricing_strategies.id');
        $this->addFilter('scope_type', 'pricing_strategies.scope_type');
        $this->addFilter('is_active', 'pricing_strategies.is_active');
        $this->addFilter('priority', 'pricing_strategies.priority');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('pricing::app.strategies.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'scope_type',
            'label'      => trans('pricing::app.strategies.datagrid.scope-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $label = trans("pricing::app.strategies.scope-types.{$row->scope_type}");

                return ucfirst($label);
            },
        ]);

        $this->addColumn([
            'index'      => 'scope_id',
            'label'      => trans('pricing::app.strategies.datagrid.scope'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                if ($row->scope_type === 'global' || $row->scope_id === null) {
                    return trans('pricing::app.strategies.datagrid.global');
                }

                // Resolve scope name based on type
                $name = match ($row->scope_type) {
                    'category' => DB::table('categories')
                        ->where('id', $row->scope_id)
                        ->value('code') ?? "#{$row->scope_id}",
                    'channel'  => DB::table('channels')
                        ->where('id', $row->scope_id)
                        ->value('name') ?? "#{$row->scope_id}",
                    'product'  => DB::table('products')
                        ->where('id', $row->scope_id)
                        ->value('sku') ?? "#{$row->scope_id}",
                    default    => "#{$row->scope_id}",
                };

                return $name;
            },
        ]);

        $this->addColumn([
            'index'      => 'minimum_margin_percentage',
            'label'      => trans('pricing::app.strategies.datagrid.min-margin'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->minimum_margin_percentage, 2).'%',
        ]);

        $this->addColumn([
            'index'      => 'target_margin_percentage',
            'label'      => trans('pricing::app.strategies.datagrid.target-margin'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->target_margin_percentage, 2).'%',
        ]);

        $this->addColumn([
            'index'      => 'premium_margin_percentage',
            'label'      => trans('pricing::app.strategies.datagrid.premium-margin'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->premium_margin_percentage, 2).'%',
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('pricing::app.strategies.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if ($row->is_active) {
                    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">'.trans('pricing::app.strategies.datagrid.active').'</span>';
                }

                return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">'.trans('pricing::app.strategies.datagrid.inactive').'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'priority',
            'label'      => trans('pricing::app.strategies.datagrid.priority'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('pricing.strategies.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pricing::app.strategies.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pricing.strategies.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pricing.strategies.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pricing::app.strategies.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pricing.strategies.destroy', $row->id);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        // No mass actions for strategies
    }
}
