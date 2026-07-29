<?php

namespace Webkul\ProductPassport\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class PassportTemplateDataGrid extends DataGrid
{
    protected $sortColumn = 'code';

    protected $sortOrder = 'asc';

    /**
     * Counts are aggregated in SQL rather than by loading each template's
     * relations, so the grid stays one query as templates and fields grow.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('passport_templates')
            ->leftJoin('passport_template_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.passport_template_id', '=', 'passport_templates.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->leftJoin(DB::raw('(
                select passport_template_id,
                    count(*) as field_count,
                    count(case when is_required then 1 end) as required_count,
                    count(case when is_required and (source_type = \'fixed\' or attribute_id is not null) then 1 end) as sourced_count
                from passport_template_fields
                group by passport_template_id
            ) as field_counts'), 'field_counts.passport_template_id', '=', 'passport_templates.id')
            ->leftJoin(DB::raw('(
                select passport_template_id, count(*) as family_count
                from passport_template_families
                group by passport_template_id
            ) as family_counts'), 'family_counts.passport_template_id', '=', 'passport_templates.id')
            ->select(
                'passport_templates.id',
                'passport_templates.code',
                'passport_templates.is_enabled',
                'requested_translation.name as name',
                DB::raw('coalesce(family_counts.family_count, 0) as family_count'),
                DB::raw('coalesce(field_counts.field_count, 0) as field_count'),
                DB::raw('coalesce(field_counts.required_count, 0) as required_count'),
                DB::raw('coalesce(field_counts.sourced_count, 0) as sourced_count'),
            );

        $this->addFilter('name', 'requested_translation.name');
        $this->addFilter('code', 'passport_templates.code');
        $this->addFilter('is_enabled', 'passport_templates.is_enabled');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('passport::app.templates.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('passport::app.templates.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'family_count',
            'label'      => trans('passport::app.templates.datagrid.families'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'field_count',
            'label'      => trans('passport::app.templates.datagrid.fields'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'sourced_count',
            'label'      => trans('passport::app.templates.datagrid.readiness'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => trans('passport::app.templates.datagrid.readiness-value', [
                'sourced'  => $row->sourced_count,
                'required' => $row->required_count,
            ]),
        ]);

        $this->addColumn([
            'index'      => 'is_enabled',
            'label'      => trans('passport::app.templates.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        [
                            'label' => trans('passport::app.templates.datagrid.enabled'),
                            'value' => 1,
                        ], [
                            'label' => trans('passport::app.templates.datagrid.disabled'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure' => fn ($row): string => $row->is_enabled
                ? trans('passport::app.templates.datagrid.enabled')
                : trans('passport::app.templates.datagrid.disabled'),
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.passport.template.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('passport::app.templates.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.catalog.passports.templates.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('catalog.passport.template.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('passport::app.templates.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.catalog.passports.templates.delete', $row->id),
            ]);
        }
    }
}
