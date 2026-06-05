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
        $tablePrefix = DB::getTablePrefix();
        $locale = app()->getLocale();

        $familyLabelQuery = $this->getFamilyLabelQuery($driver, $tablePrefix);
        $standardUnitLabelQuery = $this->getStandardUnitLabelQuery($driver, $tablePrefix);
        $standardUnitFilterQuery = $this->getStandardUnitFilterQuery($driver, $standardUnitLabelQuery, $tablePrefix);
        $unitCountQuery = $this->getUnitCountQuery($tablePrefix);

        $queryBuilder = DB::table('measurement_families')
            ->leftJoin('measurement_family_translations as mft_loc', function ($join) use ($locale) {
                $join->on('mft_loc.measurement_family_id', '=', 'measurement_families.id')
                    ->where('mft_loc.locale', '=', $locale);
            })
            ->leftJoin('measurement_family_translations as mft_en', function ($join) {
                $join->on('mft_en.measurement_family_id', '=', 'measurement_families.id')
                    ->where('mft_en.locale', '=', 'en_US');
            })
            ->addSelect(
                'measurement_families.id',
                'measurement_families.code',
                'measurement_families.standard_unit',
                'measurement_families.created_at',
                'measurement_families.updated_at',
                DB::raw($familyLabelQuery.' as family_label'),
                DB::raw($standardUnitLabelQuery.' as standard_unit_label'),
                DB::raw($unitCountQuery.' as unit_count')
            );

        $this->addFilter('id', 'measurement_families.id');
        $this->addFilter('labels', DB::raw($familyLabelQuery));
        $this->addFilter('code', 'measurement_families.code');
        $this->addFilter('standard_unit', DB::raw($standardUnitFilterQuery));

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Localized family-label expression (translation -> en_US -> [code]).
     *
     * @return string
     */
    protected function getFamilyLabelQuery(string $driver, string $tablePrefix = '')
    {
        if ($driver === 'pgsql') {
            return "COALESCE(
                NULLIF(TRIM({$tablePrefix}mft_loc.label), ''),
                NULLIF(TRIM({$tablePrefix}mft_en.label), ''),
                '[' || {$tablePrefix}measurement_families.code || ']'
            )";
        }

        return "COALESCE(
            NULLIF(TRIM({$tablePrefix}mft_loc.label), ''),
            NULLIF(TRIM({$tablePrefix}mft_en.label), ''),
            CONCAT('[', {$tablePrefix}measurement_families.code, ']')
        )";
    }

    /**
     * Localized standard-unit label expression via correlated subquery
     * (translation -> en_US -> [standard_unit]).
     *
     * @return string
     */
    protected function getStandardUnitLabelQuery(string $driver, string $tablePrefix = '')
    {
        $locale = preg_replace('/[^A-Za-z0-9_]/', '', app()->getLocale());

        $fallback = $driver === 'pgsql'
            ? "'[' || {$tablePrefix}measurement_families.standard_unit || ']'"
            : "CONCAT('[', {$tablePrefix}measurement_families.standard_unit, ']')";

        return "COALESCE((
            SELECT COALESCE(
                NULLIF(TRIM(su_loc.label), ''),
                NULLIF(TRIM(su_en.label), '')
            )
            FROM {$tablePrefix}measurement_units su
            LEFT JOIN {$tablePrefix}measurement_unit_translations su_loc
                ON su_loc.measurement_unit_id = su.id AND su_loc.locale = '{$locale}'
            LEFT JOIN {$tablePrefix}measurement_unit_translations su_en
                ON su_en.measurement_unit_id = su.id AND su_en.locale = 'en_US'
            WHERE su.measurement_family_id = {$tablePrefix}measurement_families.id
                AND su.code = {$tablePrefix}measurement_families.standard_unit
            LIMIT 1
        ), {$fallback})";
    }

    /**
     * Standard-unit filter expression (label + raw code), for searching.
     *
     * @return string
     */
    protected function getStandardUnitFilterQuery(string $driver, string $standardUnitLabelQuery, string $tablePrefix = '')
    {
        if ($driver === 'pgsql') {
            return "({$standardUnitLabelQuery} || ' ' || {$tablePrefix}measurement_families.standard_unit)";
        }

        return "CONCAT({$standardUnitLabelQuery}, ' ', {$tablePrefix}measurement_families.standard_unit)";
    }

    /**
     * Count of units belonging to the family via correlated subquery.
     *
     * @return string
     */
    protected function getUnitCountQuery(string $tablePrefix = '')
    {
        return "(
            SELECT COUNT(*)
            FROM {$tablePrefix}measurement_units uc
            WHERE uc.measurement_family_id = {$tablePrefix}measurement_families.id
        )";
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
                return $row->family_label;
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
                return $row->standard_unit_label;
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
                'title'   => 'Delete',
                'method'  => 'POST',
                'url'     => route('admin.measurement.families.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
