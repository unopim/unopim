<?php

namespace Webkul\Measurement\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MeasurementFamilyDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $driver = DB::connection()->getDriverName();
        $familyLabelQuery = $this->getFamilyLabelQuery($driver);
        $standardUnitLabelQuery = $this->getStandardUnitLabelQuery($driver);
        $standardUnitFilterQuery = $this->getStandardUnitFilterQuery($driver, $standardUnitLabelQuery);

        $unitCountQuery = $driver === 'pgsql'
            ? DB::raw('COALESCE(json_array_length(units::json), 0) as unit_count')
            : DB::raw('COALESCE(JSON_LENGTH(units), 0) as unit_count');

        $queryBuilder = DB::table('measurement_families')
            ->addSelect(
                'measurement_families.id',
                'measurement_families.labels',
                'measurement_families.code',
                'measurement_families.standard_unit',
                'measurement_families.units',
                'measurement_families.created_at',
                'measurement_families.updated_at',
                DB::raw($familyLabelQuery.' as family_label'),
                DB::raw($standardUnitLabelQuery.' as standard_unit_label'),
                $unitCountQuery
            );

        $this->addFilter('id', 'measurement_families.id');
        $this->addFilter('labels', DB::raw($familyLabelQuery));
        $this->addFilter('code', 'measurement_families.code');
        $this->addFilter('standard_unit', DB::raw($standardUnitFilterQuery));

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Get family label query.
     *
     * @return string
     */
    protected function getFamilyLabelQuery(string $driver)
    {
        $locale = preg_replace('/[^A-Za-z0-9_]/', '', app()->getLocale());

        if ($driver === 'pgsql') {
            return "COALESCE(
                NULLIF(TRIM(measurement_families.labels::jsonb->>'{$locale}'), ''),
                NULLIF(TRIM(measurement_families.labels::jsonb->>'en_US'), ''),
                '[' || measurement_families.code || ']'
            )";
        }

        return "COALESCE(
            NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(measurement_families.labels, '$.{$locale}'))), ''),
            NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(measurement_families.labels, '$.en_US'))), ''),
            CONCAT('[', measurement_families.code, ']')
        )";
    }

    /**
     * Get standard unit label query.
     *
     * @return string
     */
    protected function getStandardUnitLabelQuery(string $driver)
    {
        $locale = preg_replace('/[^A-Za-z0-9_]/', '', app()->getLocale());

        if ($driver === 'pgsql') {
            return "COALESCE((
                SELECT COALESCE(
                    NULLIF(TRIM(unit->'labels'->>'{$locale}'), ''),
                    NULLIF(TRIM(unit->'labels'->>'en_US'), '')
                )
                FROM jsonb_array_elements(COALESCE(measurement_families.units::jsonb, '[]'::jsonb)) AS unit
                WHERE unit->>'code' = measurement_families.standard_unit
                LIMIT 1
            ), '[' || measurement_families.standard_unit || ']')";
        }

        return "COALESCE((
            SELECT COALESCE(
                NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(unit.value, '$.labels.{$locale}'))), ''),
                NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(unit.value, '$.labels.en_US'))), '')
            )
            FROM JSON_TABLE(
                measurement_families.units,
                '$[*]' COLUMNS (value JSON PATH '$')
            ) AS unit
            WHERE JSON_UNQUOTE(JSON_EXTRACT(unit.value, '$.code')) = measurement_families.standard_unit
            LIMIT 1
        ), CONCAT('[', measurement_families.standard_unit, ']'))";
    }

    /**
     * Get standard unit filter query.
     *
     * @return string
     */
    protected function getStandardUnitFilterQuery(string $driver, string $standardUnitLabelQuery)
    {
        if ($driver === 'pgsql') {
            return "({$standardUnitLabelQuery} || ' ' || measurement_families.standard_unit)";
        }

        return "CONCAT({$standardUnitLabelQuery}, ' ', measurement_families.standard_unit)";
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'labels',
            'label'      => trans('measurement::app.datagrid.labels'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                $labels = json_decode($row->labels ?? '{}', true);

                return $this->getLocalizedLabel($labels, $row->code);
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
            'searchable' => false,
            'sortable'   => false,
            'filterable' => true,
            'closure'    => function ($row) {

                $units = json_decode($row->units ?? '[]', true);
                $standardUnitCode = $row->standard_unit;

                if (! empty($units)) {
                    foreach ($units as $unit) {
                        if (($unit['code'] ?? null) === $standardUnitCode) {

                            return $this->getLocalizedLabel($unit['labels'] ?? [], $standardUnitCode);
                        }
                    }
                }

                return $standardUnitCode ? '['.$standardUnitCode.']' : '-';
            },
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
        if (bouncer()->hasPermission('catalog.measurements.families.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => 'Edit',
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.measurement.families.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.measurements.families.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => 'Delete',
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.measurement.families.delete', $row->id);
                },
            ]);
        }
    }

    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('catalog.measurements.families.mass_delete')) {
            $this->addMassAction([
                'title'  => 'Delete Selected',
                'method' => 'POST',
                'url'    => route('admin.measurement.families.mass_delete'),
            ]);
        }
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
}
