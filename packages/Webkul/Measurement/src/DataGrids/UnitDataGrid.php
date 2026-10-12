<?php

namespace Webkul\Measurement\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class UnitDataGrid extends DataGrid
{
    protected $primaryColumn = 'code';

    protected $familyId;

    /**
     * Set the measurement family whose units are listed.
     */
    public function setFamilyId($id): void
    {
        $this->familyId = $id;
    }

    /**
     * Prepare the query builder.
     */
    public function prepareQueryBuilder()
    {
        $family = DB::table('measurement_families')
            ->where('id', $this->familyId)
            ->first();

        $standardUnit = $family->standard_unit ?? null;

        $driver = DB::connection()->getDriverName();
        $tablePrefix = DB::getTablePrefix();
        $locale = app()->getLocale();

        $labelExpression = $this->getUnitLabelExpression($driver, $tablePrefix);

        $isStandardExpression = "CASE WHEN {$tablePrefix}measurement_units.code = ? THEN 1 ELSE 0 END";

        $inner = DB::table('measurement_units')
            ->leftJoin('measurement_unit_translations as u_loc', function ($join) use ($locale): void {
                $join->on('u_loc.measurement_unit_id', '=', 'measurement_units.id')
                    ->where('u_loc.locale', '=', $locale);
            })
            ->leftJoin('measurement_unit_translations as u_en', function ($join): void {
                $join->on('u_en.measurement_unit_id', '=', 'measurement_units.id')
                    ->where('u_en.locale', '=', 'en_US');
            })
            ->where('measurement_units.measurement_family_id', $this->familyId)
            ->selectRaw(
                "{$tablePrefix}measurement_units.code as code, "
                ."{$labelExpression} as label, "
                ."{$tablePrefix}measurement_units.symbol as symbol, "
                ."{$isStandardExpression} as is_standard",
                [$standardUnit]
            );

        $query = DB::query()
            ->fromSub($inner, 'measurement_units')
            ->select('code', 'label', 'symbol', 'is_standard');

        $this->setQueryBuilder($query);

        return $query;
    }

    /**
     * Build the localized unit-label SQL expression, falling back to the
     * en_US translation and finally to the bracketed unit code.
     */
    protected function getUnitLabelExpression(string $driver, string $tablePrefix = ''): string
    {
        if ($driver === 'pgsql') {
            return "COALESCE(
                NULLIF(TRIM({$tablePrefix}u_loc.label), ''),
                NULLIF(TRIM({$tablePrefix}u_en.label), ''),
                '[' || {$tablePrefix}measurement_units.code || ']'
            )";
        }

        return "COALESCE(
            NULLIF(TRIM({$tablePrefix}u_loc.label), ''),
            NULLIF(TRIM({$tablePrefix}u_en.label), ''),
            CONCAT('[', {$tablePrefix}measurement_units.code, ']')
        )";
    }

    /**
     * Add the columns to the datagrid.
     */
    public function prepareColumns(): void
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
                            'label' => trans('measurement::app.datagrid.standard'),
                            'value' => 1,
                        ], [
                            'label' => '-',
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => fn ($row) => $row->is_standard
                ? "<span class='label-active'>STANDARD UNIT</span>"
                : '',
        ]);

    }

    /**
     * Apply the requested filters to the query.
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return parent::processRequestedFilters($requestedFilters);
        }

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($query) use ($requestedValues): void {
                    foreach ($requestedValues as $value) {
                        $query
                            ->orWhere('code', 'ILIKE', '%'.$value.'%')
                            ->orWhere('label', 'ILIKE', '%'.$value.'%');
                    }
                });

                continue;
            }

            if (in_array($requestedColumn, ['code', 'label'])) {
                $this->queryBuilder->where(function ($query) use ($requestedColumn, $requestedValues): void {
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
     * Prepare the row actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.measurements.units.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('measurement::app.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.measurement.families.units.edit', [
                    'familyId' => $this->familyId,
                    'code'     => $row->code,
                ]),
            ]);
        }

        if (bouncer()->hasPermission('catalog.measurements.units.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('measurement::app.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row): ?string {

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
}
