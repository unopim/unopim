<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Core\Facades\ElasticSearch;
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
            )
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
     * Process request.
     */
    public function processRequest(): void
    {
        if (! env('ELASTICSEARCH_ENABLED', false)) {
            parent::processRequest();

            return;
        }

        // Additional logic specific to ProductDataGrid
        $params = $this->validatedRequest();
        $pagination = $params['pagination'];

        $results = Elasticsearch::search([
            'index' => strtolower('categories'),
            'body'  => [
                'from'          => ($pagination['page'] * $pagination['per_page']) - $pagination['per_page'],
                'size'          => $pagination['per_page'],
                'stored_fields' => [],
                'query'         => [
                    'bool' => $this->getElasticFilters($params['filters'] ?? []) ?: new \stdClass,
                ],
                'sort'          => $this->getElasticSort($params['sort'] ?? []),
            ],
        ]);

        $ids = collect($results['hits']['hits'])->pluck('_id')->toArray();

        $this->queryBuilder->whereIn('cat.id', $ids)
            ->orderBy(DB::raw('FIELD(cat.id, '.implode(',', $ids).')'));

        $total = $results['hits']['total']['value'];

        $this->paginator = new LengthAwarePaginator(
            $total ? $this->queryBuilder->get() : [],
            $total,
            $pagination['per_page'],
            $pagination['page'],
            [
                'path'  => request()->url(),
                'query' => [],
            ]
        );
    }

    /**
     * Process request.
     */
    protected function getElasticFilters($params): array
    {
        $filters = [];

        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }

            if ($attribute == 'all') {
                $attribute = 'name';
            }

            $filters['filter'][] = $this->getFilterValue($attribute, $value);
        }

        return $filters;
    }

    /**
     * Return applied filters
     */
    public function getFilterValue(mixed $attribute, mixed $values): array
    {
        switch ($attribute) {
            case 'category_id':
                return [
                    'terms' => [
                        'id' => $values,
                    ],
                ];

            case 'name':
                return [
                    'terms' => [
                        'name' => $values,
                    ],
                ];

            default:
                return [
                    'terms' => [
                        $attribute => $values,
                    ],
                ];
        }
    }

    /**
     * Process request.
     */
    protected function getElasticSort($params): array
    {
        $sort = $params['column'] ?? $this->primaryColumn;

        if ($sort == 'name') {
            $sort .= '.keyword';
        }

        if ($sort == 'category_id') {
            $sort = 'id';
        }

        return [
            $sort => [
                'order' => $params['order'] ?? $this->sortOrder,
            ],
        ];
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
