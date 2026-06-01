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
            $query = DB::query()
                ->fromSub(
                    DB::query()->selectRaw('NULL as code, NULL as label, NULL as symbol, 0 as is_standard'),
                    'measurement_units'
                )
                ->select('code', 'label', 'symbol', 'is_standard')
                ->whereRaw('1 = 0');

            $this->setQueryBuilder($query);

            return $query;
        }

        $queryList = [];

        foreach ($units as $unit) {

            $code = $unit['code'] ?? '';
            $label = $this->getLocalizedLabel($unit['labels'] ?? [], $code);
            $symbol = $unit['symbol'] ?? '';

            $isStd = ($code === $standardUnit) ? 1 : 0;

            $queryList[] = DB::query()
                ->selectRaw(
                    '? as code, ? as label, ? as symbol, ? as is_standard',
                    [$code, $label, $symbol, $isStd]
                );
        }

        $finalQuery = array_shift($queryList);

        foreach ($queryList as $q) {
            $finalQuery = $finalQuery->unionAll($q);
        }

        $finalQuery = DB::query()
            ->fromSub($finalQuery, 'measurement_units')
            ->select('code', 'label', 'symbol', 'is_standard');

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
            'filterable' => false,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('measurement::app.datagrid.labels'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => false,
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

    public function processRequestedFilters(array $requestedFilters)
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return parent::processRequestedFilters($requestedFilters);
        }

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($query) use ($requestedValues) {
                    foreach ($requestedValues as $value) {
                        $query
                            ->orWhere('code', 'ILIKE', '%'.$value.'%')
                            ->orWhere('label', 'ILIKE', '%'.$value.'%');
                    }
                });

                continue;
            }

            if (in_array($requestedColumn, ['code', 'label'])) {
                $this->queryBuilder->where(function ($query) use ($requestedColumn, $requestedValues) {
                    foreach ($requestedValues as $value) {
                        $query->orWhere($requestedColumn, 'ILIKE', '%'.$value.'%');
                    }
                });

                continue;
            }

            parent::processRequestedFilters([$requestedColumn => $requestedValues]);
        }

        return $this->queryBuilder;
    }

    /**
     * Get localized label.
     *
     * @return string
     */
    protected function getLocalizedLabel(array $labels, ?string $code)
    {
        $locale = app()->getLocale();
        $label = $labels[$locale] ?? $labels['en_US'] ?? null;

        if (is_string($label) && trim($label) !== '') {
            return $label;
        }

        return $code ? '['.$code.']' : '-';
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

                if ($row->is_standard) {
                    return null;
                }

                return route('admin.measurement.families.units.delete', [
                    'familyId' => $this->familyId,
                    'code'     => $row->code,
                ]);
            },
        ]);
    }
}
