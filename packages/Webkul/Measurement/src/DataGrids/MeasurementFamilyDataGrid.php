<?php

namespace Webkul\Measurement\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MeasurementFamilyDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    /**
     * Prepare the query builder.
     */
    public function prepareQueryBuilder()
    {
        $driver = DB::connection()->getDriverName();
        $locale = app()->getLocale();

        $familyLabelQuery = $this->getFamilyLabelQuery($driver);
        $standardUnitLabelQuery = $this->getStandardUnitLabelQuery($driver);
        $standardUnitFilterQuery = $this->getStandardUnitFilterQuery($driver, $standardUnitLabelQuery);
        $unitCountQuery = $this->getUnitCountQuery();

        $queryBuilder = DB::table('measurement_families')
            ->leftJoin('measurement_family_translations as mft_loc', function ($join) use ($locale): void {
                $join->on('mft_loc.measurement_family_id', '=', 'measurement_families.id')
                    ->where('mft_loc.locale', '=', $locale);
            })
            ->leftJoin('measurement_family_translations as mft_en', function ($join): void {
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
     */
    protected function getFamilyLabelQuery(string $driver): string
    {
        $grammar = DB::getQueryGrammar();

        if ($driver === 'pgsql') {
            return "COALESCE(
                NULLIF(TRIM({$grammar->wrap('mft_loc.label')}), ''),
                NULLIF(TRIM({$grammar->wrap('mft_en.label')}), ''),
                '[' || {$grammar->wrap('measurement_families.code')} || ']'
            )";
        }

        return "COALESCE(
            NULLIF(TRIM({$grammar->wrap('mft_loc.label')}), ''),
            NULLIF(TRIM({$grammar->wrap('mft_en.label')}), ''),
            CONCAT('[', {$grammar->wrap('measurement_families.code')}, ']')
        )";
    }

    /**
     * Localized standard-unit label expression via correlated subquery
     * (translation -> en_US -> [standard_unit]).
     */
    protected function getStandardUnitLabelQuery(string $driver): string
    {
        $grammar = DB::getQueryGrammar();

        $locale = preg_replace('/[^A-Za-z0-9_]/', '', app()->getLocale());

        $fallback = $driver === 'pgsql'
            ? "'[' || {$grammar->wrap('measurement_families.standard_unit')} || ']'"
            : "CONCAT('[', {$grammar->wrap('measurement_families.standard_unit')}, ']')";

        return "COALESCE((
            SELECT COALESCE(
                NULLIF(TRIM(su_loc.label), ''),
                NULLIF(TRIM(su_en.label), '')
            )
            FROM {$grammar->wrapTable('measurement_units')} su
            LEFT JOIN {$grammar->wrapTable('measurement_unit_translations')} su_loc
                ON su_loc.measurement_unit_id = su.id AND su_loc.locale = '{$locale}'
            LEFT JOIN {$grammar->wrapTable('measurement_unit_translations')} su_en
                ON su_en.measurement_unit_id = su.id AND su_en.locale = 'en_US'
            WHERE su.measurement_family_id = {$grammar->wrap('measurement_families.id')}
                AND su.code = {$grammar->wrap('measurement_families.standard_unit')}
            LIMIT 1
        ), {$fallback})";
    }

    /**
     * Standard-unit filter expression (label + raw code), for searching.
     */
    protected function getStandardUnitFilterQuery(string $driver, string $standardUnitLabelQuery): string
    {
        $grammar = DB::getQueryGrammar();

        if ($driver === 'pgsql') {
            return "({$standardUnitLabelQuery} || ' ' || {$grammar->wrap('measurement_families.standard_unit')})";
        }

        return "CONCAT({$standardUnitLabelQuery}, ' ', {$grammar->wrap('measurement_families.standard_unit')})";
    }

    /**
     * Count of units belonging to the family via correlated subquery.
     */
    protected function getUnitCountQuery(): string
    {
        $grammar = DB::getQueryGrammar();

        return "(
            SELECT COUNT(*)
            FROM {$grammar->wrapTable('measurement_units')} uc
            WHERE uc.measurement_family_id = {$grammar->wrap('measurement_families.id')}
        )";
    }

    /**
     * Add the columns to the datagrid.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'labels',
            'label'      => trans('measurement::app.datagrid.labels'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
            'closure'    => fn ($row) => $row->family_label,
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
            'closure'    => fn ($row) => $row->standard_unit_label,
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

    /**
     * Prepare the row actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.measurements.families.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('measurement::app.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.measurement.families.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('catalog.measurements.families.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => trans('measurement::app.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.measurement.families.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare the mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('catalog.measurements.families.mass_delete')) {
            $this->addMassAction([
                'title'               => trans('measurement::app.datagrid.delete'),
                'method'              => 'POST',
                'supports_select_all' => true,
                'url'                 => route('admin.measurement.families.mass_delete'),
                'options'             => ['actionType' => 'delete'],
            ]);
        }
    }

    /**
     * The grid has no id column and its joined tables carry one, so the bare key would be ambiguous.
     */
    protected function getPrimaryDatabaseColumn(): string
    {
        return 'measurement_families.id';
    }
}
