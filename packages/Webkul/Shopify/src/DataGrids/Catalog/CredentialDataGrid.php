<?php

namespace Webkul\Shopify\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('wk_shopify_credentials_config')
            ->select(
                'id',
                'shopUrl',
                'apiVersion',
                'active'
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
            'index'      => 'shopUrl',
            'label'      => trans('shopify::app.shopify.credential.datagrid.shopUrl'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'apiVersion',
            'label'      => trans('shopify::app.shopify.credential.datagrid.apiVersion'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'active',
            'label'      => trans('shopify::app.shopify.credential.datagrid.enabled'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->active ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>' : '<span class="label-info">'.trans('admin::app.common.no').'</span>',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('shopify.credentials.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('shopify.credentials.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('shopify.credentials.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('shopify.credentials.delete', $row->id);
                },
            ]);
        }
    }
}
