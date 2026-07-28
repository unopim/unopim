<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\GrammarQueryManager;
use Webkul\DataGrid\DataGrid;

class VariantStructureDataGrid extends DataGrid
{
    protected $sortColumn = 'id';

    public function __construct(protected int $familyId) {}

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('variant_structures')
            ->select(
                'variant_structures.id',
                'variant_structures.code',
                'variant_structures.name',
                'variant_structures.levels',
                DB::raw($this->axisCodesSubQuery('level_1').' as level_1_axes'),
                DB::raw($this->axisCodesSubQuery('level_2').' as level_2_axes')
            )
            ->where('variant_structures.attribute_family_id', $this->familyId);

        $this->addFilter('id', 'variant_structures.id');
        $this->addFilter('code', 'variant_structures.code');
        $this->addFilter('name', 'variant_structures.name');
        $this->addFilter('levels', 'variant_structures.levels');

        return $queryBuilder;
    }

    /**
     * Correlated sub-query returning the axis attribute codes of one level as a
     * single ordered, comma-separated string. Prefixes are interpolated because
     * DB::raw() bypasses them; the aggregate comes from the driver grammar.
     */
    protected function axisCodesSubQuery(string $level): string
    {
        $prefix = DB::getTablePrefix();

        $aggregate = GrammarQueryManager::getGrammar()->groupConcat(
            "{$prefix}attributes.code",
            orderBy: "{$prefix}variant_structure_axes.position",
        );

        return "(
            SELECT {$aggregate}
            FROM {$prefix}variant_structure_axes
            INNER JOIN {$prefix}attributes ON {$prefix}attributes.id = {$prefix}variant_structure_axes.attribute_id
            WHERE {$prefix}variant_structure_axes.variant_structure_id = {$prefix}variant_structures.id
                AND {$prefix}variant_structure_axes.level = '{$level}'
        )";
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'visible'    => false,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.families.edit.variant'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.families.edit.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'levels',
            'label'      => trans('admin::app.catalog.families.edit.structure'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => (int) $row->levels === 2
                ? trans('admin::app.catalog.families.edit.parent-sub-parent-child')
                : trans('admin::app.catalog.families.edit.parent-child'),
        ]);

        $this->addColumn([
            'index'      => 'level_1_axes',
            'label'      => trans('admin::app.catalog.families.edit.level-1-axes'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->level_1_axes ?: '-',
        ]);

        $this->addColumn([
            'index'      => 'level_2_axes',
            'label'      => trans('admin::app.catalog.families.edit.level-2-axes'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->level_2_axes ?: '-',
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.families.variant-structures.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.families.edit.edit-variant'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.families.variant-structures.edit', [$this->familyId, $row->id]);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.families.variant-structures.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => trans('admin::app.catalog.families.edit.delete-variant'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.families.variant-structures.delete', [$this->familyId, $row->id]);
                },
            ]);
        }
    }
}
