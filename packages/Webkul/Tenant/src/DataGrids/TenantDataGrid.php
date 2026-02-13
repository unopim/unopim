<?php

namespace Webkul\Tenant\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class TenantDataGrid extends DataGrid
{
    /**
     * The tenants table is the root entity — it has no tenant_id column.
     * Override to prevent the base class from adding a WHERE tenant_id clause.
     */
    protected function applyTenantScope(): void
    {
        // No-op: tenants table is not tenant-scoped.
    }

    /**
     * Prepare query builder — NO TenantScope, shows all tenants.
     */
    public function prepareQueryBuilder()
    {
        return DB::table('tenants')
            ->addSelect('id', 'name', 'domain', 'status', 'created_at');
    }

    /**
     * Add columns.
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('tenant::app.tenants.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('tenant::app.tenants.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'domain',
            'label'      => trans('tenant::app.tenants.datagrid.domain'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('tenant::app.tenants.datagrid.status'),
            'type'       => 'dropdown',
            'options'    => [
                ['label' => trans('tenant::app.tenants.status.provisioning'), 'value' => 'provisioning'],
                ['label' => trans('tenant::app.tenants.status.active'), 'value' => 'active'],
                ['label' => trans('tenant::app.tenants.status.suspended'), 'value' => 'suspended'],
                ['label' => trans('tenant::app.tenants.status.deleting'), 'value' => 'deleting'],
                ['label' => trans('tenant::app.tenants.status.deleted'), 'value' => 'deleted'],
            ],
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => '<span class="badge badge-'.$row->status.'">'
                .trans('tenant::app.tenants.status.'.$row->status)
                .'</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('tenant::app.tenants.datagrid.created-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.tenants.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('tenant::app.tenants.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.tenants.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.tenants.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('tenant::app.tenants.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.tenants.destroy', $row->id);
                },
            ]);
        }
    }
}
