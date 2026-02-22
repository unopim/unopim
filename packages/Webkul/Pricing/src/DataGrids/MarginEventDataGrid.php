<?php

namespace Webkul\Pricing\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MarginEventDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('margin_protection_events')
            ->leftJoin('products', 'margin_protection_events.product_id', '=', 'products.id')
            ->leftJoin('channels', 'margin_protection_events.channel_id', '=', 'channels.id')
            ->select(
                'margin_protection_events.id',
                'products.sku as product_sku',
                'channels.name as channel_name',
                'margin_protection_events.event_type',
                'margin_protection_events.proposed_price',
                'margin_protection_events.break_even_price',
                'margin_protection_events.margin_percentage',
                'margin_protection_events.currency_code',
                'margin_protection_events.reason',
                'margin_protection_events.approved_at',
                'margin_protection_events.created_at',
            );

        $this->addFilter('id', 'margin_protection_events.id');
        $this->addFilter('product_sku', 'products.sku');
        $this->addFilter('event_type', 'margin_protection_events.event_type');
        $this->addFilter('created_at', 'margin_protection_events.created_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('pricing::app.margin-events.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_sku',
            'label'      => trans('pricing::app.margin-events.datagrid.product-sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'channel_name',
            'label'      => trans('pricing::app.margin-events.datagrid.channel'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->channel_name ?? trans('pricing::app.margin-events.datagrid.all-channels'),
        ]);

        $this->addColumn([
            'index'      => 'event_type',
            'label'      => trans('pricing::app.margin-events.datagrid.event-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $colors = [
                    'blocked'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'warning'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'expired'  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    'rejected' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                ];

                $colorClass = $colors[$row->event_type] ?? $colors['expired'];
                $label = trans("pricing::app.margin-events.event-types.{$row->event_type}");

                return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$colorClass.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'proposed_price',
            'label'      => trans('pricing::app.margin-events.datagrid.proposed-price'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->proposed_price, 2).' '.$row->currency_code,
        ]);

        $this->addColumn([
            'index'      => 'break_even_price',
            'label'      => trans('pricing::app.margin-events.datagrid.break-even-price'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => number_format((float) $row->break_even_price, 2).' '.$row->currency_code,
        ]);

        $this->addColumn([
            'index'      => 'margin_percentage',
            'label'      => trans('pricing::app.margin-events.datagrid.margin-percentage'),
            'type'       => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => function ($row) {
                $value = number_format((float) $row->margin_percentage, 2).'%';
                $colorClass = (float) $row->margin_percentage < 0
                    ? 'text-red-600 dark:text-red-400'
                    : 'text-green-600 dark:text-green-400';

                return '<span class="font-semibold '.$colorClass.'">'.$value.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('pricing::app.margin-events.datagrid.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('pricing.margins.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('pricing::app.margin-events.datagrid.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pricing.margins.show', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pricing.margins.approve')) {
            $this->addAction([
                'icon'   => 'icon-check',
                'title'  => trans('pricing::app.margin-events.datagrid.approve'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pricing.margins.show', $row->id);
                },
                'condition' => fn ($row) => $row->event_type === 'blocked' && $row->approved_at === null,
            ]);
        }
    }

    public function prepareMassActions()
    {
        // No mass actions for margin events
    }
}
