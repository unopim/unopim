<?php

namespace Webkul\Shopify\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MetaFieldDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('wk_shopify_metafield_defination')
            ->select(
                'id',
                'ownerType',
                'attributeLabel',
                'attribute',
                'ContentTypeName',
                'type',
                'code',
                'pin'
            );

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'ownerType',
            'label'      => trans('shopify::app.shopify.metafield.datagrid.definitiontype'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('shopify::app.shopify.metafield.datagrid.attribute-label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'attribute',
            'label'      => trans('shopify::app.shopify.metafield.datagrid.definitionName'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'ContentTypeName',
            'label'      => trans('shopify::app.shopify.metafield.datagrid.contentTypeName'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => ! empty($row->ContentTypeName) ? $row->ContentTypeName : $row->type,
        ]);

        $this->addColumn([
            'index'      => 'pin',
            'label'      => trans('shopify::app.shopify.metafield.datagrid.pin'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->pin ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('shopify.meta-fields.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('shopify.metafield.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('shopify.meta-fields.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('shopify.metafield.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions for delete MetaField Defintion.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('shopify.meta-fields.delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.products.index.datagrid.delete'),
                'url'     => route('shopify.metafield.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
