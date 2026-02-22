<?php

namespace Webkul\Order\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * Profitability DataGrid
 *
 * Provides profitability analysis grid with revenue, cost,
 * profit, and margin calculations per order.
 */
class ProfitabilityDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'order_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('orders')
            ->leftJoin('channels', 'orders.channel_id', '=', 'channels.id')
            ->select(
                'orders.id as order_id',
                'orders.order_number',
                'channels.name as channel_name',
                'orders.total_amount as revenue',
                'orders.currency_code',
                'orders.order_date'
            )
            ->whereNull('orders.deleted_at');

        $this->addFilter('order_id', 'orders.id');
        $this->addFilter('order_number', 'orders.order_number');
        $this->addFilter('channel_id', 'orders.channel_id');

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
            'index' => 'order_number',
            'label' => trans('order::app.admin.profitability.datagrid.order-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('order::app.admin.profitability.datagrid.channel'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'revenue',
            'label' => trans('order::app.admin.profitability.datagrid.revenue'),
            'type' => 'number',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return ($row->currency_code ?? 'USD') . ' ' . number_format($row->revenue, 2);
            },
        ]);

        $this->addColumn([
            'index' => 'cost',
            'label' => trans('order::app.admin.profitability.datagrid.cost'),
            'type' => 'number',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $cost = $this->calculateOrderCost($row->order_id);
                return ($row->currency_code ?? 'USD') . ' ' . number_format($cost, 2);
            },
        ]);

        $this->addColumn([
            'index' => 'profit',
            'label' => trans('order::app.admin.profitability.datagrid.profit'),
            'type' => 'number',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $cost = $this->calculateOrderCost($row->order_id);
                $profit = $row->revenue - $cost;
                $class = $profit >= 0 ? 'text-success' : 'text-danger';

                return '<span class="' . $class . '">' .
                       ($row->currency_code ?? 'USD') . ' ' . number_format($profit, 2) .
                       '</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'margin_percentage',
            'label' => trans('order::app.admin.profitability.datagrid.margin'),
            'type' => 'number',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $cost = $this->calculateOrderCost($row->order_id);
                $profit = $row->revenue - $cost;
                $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                $class = $margin >= 0 ? 'text-success' : 'text-danger';

                return '<span class="' . $class . '">' . number_format($margin, 2) . '%</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'order_date',
            'label' => trans('order::app.admin.profitability.datagrid.order-date'),
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->order_date);
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
        if (bouncer()->allows('orders.view')) {
            $this->addAction([
                'index' => 'view',
                'icon' => 'icon-eye',
                'title' => trans('order::app.admin.profitability.datagrid.view-order'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.orders.show', $row->order_id);
                },
            ]);
        }
    }

    /**
     * Calculate order cost from order items.
     *
     * @param  int  $orderId
     * @return float
     */
    protected function calculateOrderCost(int $orderId): float
    {
        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get();

        $totalCost = 0;

        foreach ($items as $item) {
            $product = DB::table('products')->find($item->product_id);

            if ($product) {
                $values = json_decode($product->values, true);
                $cost = (float) ($values['common']['cost'] ?? 0);
                $totalCost += $cost * $item->quantity;
            }
        }

        return $totalCost;
    }
}
