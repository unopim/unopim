<?php

namespace Webkul\Measurement\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MeasurementFamilyDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('measurement_families')
            ->addSelect(
                'measurement_families.id',
                'measurement_families.labels',
                'measurement_families.code',
                'measurement_families.standard_unit',
                'measurement_families.created_at',
                'measurement_families.updated_at',
                DB::raw('JSON_LENGTH(units) as unit_count')
            );

        $this->addFilter('id', 'measurement_families.id');
        $this->addFilter('labels', 'measurement_families.labels');
        $this->addFilter('code', 'measurement_families.code');
        $this->addFilter('standard_unit', 'measurement_families.standard_unit');

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    public function prepareColumns()
    {

        $this->addColumn([
            'index'      => 'labels',
            'label' => trans('measurement::app.datagrid.labels'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                $labels = json_decode($row->labels ?? '{}', true);

                return $labels['en_US'] ?? '-';
            },
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('measurement::app.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'standard_unit',
            'label'      => trans('measurement::app.datagrid.standard_unit'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => false,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'unit_count',
            'label'      => trans('measurement::app.datagrid.unit_count'),
            'type'       => 'string',
            'searchable' => false,
            'sortable'   => false,
            'filterable' => false,
        ]);

    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'index'  => 'edit',
            'title'  => 'Edit',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.measurement.families.edit', $row->id);
            },
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => 'Delete',
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('admin.measurement.families.delete', $row->id);
            },
        ]);
    }

    public function prepareMassActions()
    {
        $this->addMassAction([
            'title'  => 'Delete Selected',
            'method' => 'POST',
            'url'    => route('admin.measurement.families.mass_delete'),
        ]);
    }
}
