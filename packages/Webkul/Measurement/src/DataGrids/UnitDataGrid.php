<?php

namespace Webkul\Measurement\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class UnitDataGrid extends DataGrid
{
    protected $primaryColumn = 'code';

    protected $familyId;

    public function setFamilyId($id)
    {
        $this->familyId = $id;
    }

    public function prepareQueryBuilder()
    {
        $family = DB::table('measurement_families')
            ->where('id', $this->familyId)
            ->first();

        $units = json_decode($family->units ?? '[]', true);
        $standardUnit = $family->standard_unit ?? null;

        if (empty($units)) {
            $query = DB::table('measurement_families')
                ->select(
                    DB::raw('NULL as code'),
                    DB::raw('NULL as label'),
                    DB::raw('NULL as symbol'),
                    DB::raw('0 as is_standard')
                )
                ->whereRaw('1 = 0');

            $this->setQueryBuilder($query);

            return $query;
        }

        $queryList = [];

        foreach ($units as $unit) {
            $code = $unit['code'] ?? '';
            $label = $unit['labels']['en_US'] ?? '';
            $symbol = $unit['symbol'] ?? '';
            $isStd = ($code === $standardUnit) ? 1 : 0;

            $queryList[] = DB::table(DB::raw(
                "(SELECT
                    '$code'   AS code,
                    '$label'  AS label,
                    '$symbol' AS symbol,
                    $isStd    AS is_standard
                ) temp"
            ));
        }

        $finalQuery = array_shift($queryList);

        foreach ($queryList as $q) {
            $finalQuery = $finalQuery->unionAll($q);
        }

        $this->setQueryBuilder($finalQuery);

        return $finalQuery;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('measurement::app.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('measurement::app.datagrid.labels'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'is_standard',
            'label'      => trans('measurement::app.datagrid.is_standard'),
            'type'       => 'boolean',
            'searchable' => false,
            'sortable'   => true,
            'filterable' => false,
            'escape'     => false,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => 'Standard',
                            'value' => 1,
                        ], [
                            'label' => '-',
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => function ($row) {
                return $row->is_standard
                    ? "<span class='label-active'>STANDARD UNIT</span>"
                    : '';
            },
        ]);

    }

    public function prepareActions()
    {

        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => 'Edit',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.measurement.families.units.edit', [
                    'familyId' => $this->familyId,
                    'code'     => $row->code,
                ]);
            },
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => 'Delete',
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('admin.measurement.families.units.delete', [
                    'familyId' => $this->familyId,
                    'code'     => $row->code,
                ]);
            },
        ]);
    }

    public function prepareMassActions()
    {
        $this->addMassAction([
            'title'  => 'Delete Selected',
            'method' => 'POST',
            'url'    => route('admin.measurement.families.unitmass_delete'),
        ]);
    }
}
