<?php

namespace Webkul\AdminApi\DataGrids\Integrations;

use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Traits\OauthClientGenerator;
use Webkul\DataGrid\DataGrid;

class ApiKeysDataGrid extends DataGrid
{
    use OauthClientGenerator;

    /**
     * Index.
     *
     * @var string
     */
    protected $primaryColumn = 'api_id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('api_keys as api')
            ->join('admins as u', 'api.admin_id', '=', 'u.id')
            ->leftJoin('oauth_clients as oc', function ($join) {
                $join->on('api.admin_id', '=', 'oc.user_id')
                    ->where('oc.revoked', '=', 0);
            })

            ->addSelect(
                'api.id as api_id',
                'api.name as api_name',
                'api.permission_type as api_permission_type',
                'u.name as user_name',
                'oc.id as client_id'
            )
            ->where('api.revoked', '=', 0);

        $this->addFilter('api_id', 'api.id');
        $this->addFilter('api_name', 'api.name');
        $this->addFilter('api_permission_type', 'api.permission_type');
        $this->addFilter('user_name', 'u.name');

        return $queryBuilder;
    }

    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'api_id',
            'label'      => trans('admin::app.configuration.integrations.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'width'      => '40px',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'api_name',
            'label'      => trans('admin::app.configuration.integrations.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'user_name',
            'label'      => trans('admin::app.configuration.integrations.index.datagrid.user'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'client_id',
            'label'      => trans('admin::app.configuration.integrations.index.datagrid.client-id'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return $row->client_id ? $this->maskClientIdAndScreatKey($row->client_id) : null;
            },
        ]);

        $this->addColumn([
            'index'      => 'api_permission_type',
            'label'      => trans('admin::app.configuration.integrations.index.datagrid.permission-type'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return $row->api_permission_type == 'all' ? trans('admin::app.configuration.integrations.edit.all') : trans('admin::app.configuration.integrations.edit.custom');
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
        if (bouncer()->hasPermission('configuration.integrations.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.configuration.integrations.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.configuration.integrations.edit', $row->api_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('configuration.integrations.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.configuration.integrations.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.configuration.integrations.delete', $row->api_id);
                },
            ]);
        }
    }
}
