<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Locale;
use Webkul\DataGrid\DataGrid;

class CategoryDataGrid extends DataGrid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primaryColumn = 'category_id';

    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn = 'name';

    /**
     * Default sort order of datagrid.
     *
     * @var string
     */
    protected $sortOrder = 'asc';

    /**
     * Contains the keys for which extra filters to show.
     *
     * @var string[]
     */
    protected $extraFilters = [
        'locales',
    ];

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        if (core()->getRequestedLocaleCode() === 'all') {
            $whereInLocales = Locale::query()->pluck('code')->toArray();
        } else {
            $whereInLocales = [core()->getRequestedLocaleCode()];
        }

        $tablePrefix = DB::getTablePrefix();

        $subQuery = $this->getSubQuery($whereInLocales, $tablePrefix);

        $queryBuilder = DB::table('categories as cat')
            ->select(
                'cat.id as category_id',
                'cat.code as code',
                DB::raw('CategoryNameTable.name as name'),
                DB::raw('COUNT(DISTINCT '.$tablePrefix.'pc.id) as count')
            )
            ->leftJoin('products as pc', function ($leftJoin) use ($tablePrefix) {
                $leftJoin->whereJsonContains('pc.values->categories', DB::raw('JSON_QUOTE('.$tablePrefix.'cat.code)'));
            })
            ->leftJoin(DB::raw("({$subQuery->toSql()}) as CategoryNameTable"), function ($leftJoin) {
                $leftJoin->on('cat.id', '=', DB::raw('CategoryNameTable.id'));
            })
            ->groupBy('cat.id', 'cat.code', DB::raw('CategoryNameTable.name'));

        $this->addFilter('name', DB::raw('CategoryNameTable.name'));

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.categories.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.categories.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'count',
            'label'      => trans('admin::app.catalog.categories.index.datagrid.no-of-products'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.categories.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.categories.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.categories.edit', $row->category_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.categories.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => trans('admin::app.catalog.categories.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.categories.delete', $row->category_id);
                },
            ]);
        }
    }

    /**
     * Add Datagrid Mass Actions
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('catalog.categories.delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.categories.index.datagrid.delete'),
                'method'  => 'POST',
                'url'     => route('admin.catalog.categories.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }

    /**
     * Creates a query to fetch the parent names of the categories
     */
    private function getSubQuery(array $locale, string $tablePrefix): Builder
    {
        $locales = implode(', ', $locale);

        return DB::table(DB::raw("(WITH RECURSIVE tree_view AS (
            SELECT id,
                parent_id,
                (CASE WHEN JSON_EXTRACT(additional_data, '$.locale_specific.".$locales.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(additional_data, '$.locale_specific.".$locales.".name'), '\"', '') ELSE CONCAT('[', code, ']') END) as name
            FROM ".$tablePrefix."categories
            WHERE parent_id IS NULL
            UNION ALL

            SELECT parent.id,
                parent.parent_id,
                CONCAT(tree_view.name, ' / ', (CASE WHEN JSON_EXTRACT(additional_data, '$.locale_specific.".$locales.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(additional_data, '$.locale_specific.".$locales.".name'), '\"', '') ELSE CONCAT('[', code, ']') END)) AS name
            FROM ".$tablePrefix.'categories parent
            JOIN tree_view ON parent.parent_id = tree_view.id
        )
        SELECT id, parent_id, name
        FROM tree_view
        ) as CategoryNameTable'));
    }
}
