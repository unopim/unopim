<?php

namespace Webkul\Pricing\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CostDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('product_costs')
            ->leftJoin('products', 'product_costs.product_id', '=', 'products.id')
            ->select(
                'product_costs.id',
                'products.sku as product_sku',
                'product_costs.cost_type',
                'product_costs.amount',
                'product_costs.currency_code',
                'product_costs.effective_from',
                'product_costs.effective_to',
                'product_costs.created_at',
            );

        $this->addFilter('id', 'product_costs.id');
        $this->addFilter('product_sku', 'products.sku');
        $this->addFilter('cost_type', 'product_costs.cost_type');
        $this->addFilter('currency_code', 'product_costs.currency_code');
        $this->addFilter('created_at', 'product_costs.created_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('pricing::app.costs.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_sku',
            'label'      => trans('pricing::app.costs.datagrid.product-sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'cost_type',
            'label'      => trans('pricing::app.costs.datagrid.cost-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $colors = [
                    'cogs'        => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'operational' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                    'marketing'   => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'platform'    => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                    'shipping'    => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
                    'overhead'    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                ];

                $colorClass = $colors[$row->cost_type] ?? $colors['overhead'];
                $label = trans("pricing::app.costs.cost-types.{$row->cost_type}");

                return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$colorClass.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'amount',
            'label'      => trans('pricing::app.costs.datagrid.amount'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->amount, 2).' '.$row->currency_code,
        ]);

        $this->addColumn([
            'index'      => 'currency_code',
            'label'      => trans('pricing::app.costs.datagrid.currency'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'effective_from',
            'label'      => trans('pricing::app.costs.datagrid.effective-from'),
            'type'       => 'date',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'effective_to',
            'label'      => trans('pricing::app.costs.datagrid.effective-to'),
            'type'       => 'date',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->effective_to ?? trans('pricing::app.costs.datagrid.ongoing'),
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('pricing::app.costs.datagrid.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('pricing.costs.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pricing::app.costs.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pricing.costs.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pricing.costs.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pricing::app.costs.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pricing.costs.destroy', $row->id);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        // No mass actions for cost entries
    }
}
