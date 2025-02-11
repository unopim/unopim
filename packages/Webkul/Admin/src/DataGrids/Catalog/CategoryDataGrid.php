<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataGrid\DataGrid;

class CategoryDataGrid extends DataGrid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

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
        $tablePrefix = DB::getTablePrefix();

        $localeCode = core()->getRequestedLocaleCode();

        $subQuery = $this->getSubQuery($localeCode, $tablePrefix);

        $queryBuilder = DB::table('categories as cat')
            ->select(
                'cat.id as category_id',
                'cat.code as code',
                DB::raw('(CASE WHEN JSON_EXTRACT('.$tablePrefix."cat.additional_data, '$.locale_specific.".$localeCode.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(".$tablePrefix."cat.additional_data, '$.locale_specific.".$localeCode.".name'), '\"', '') ELSE CONCAT('[', ".$tablePrefix."cat.code, ']') END) as category_name"),
                DB::raw('CategoryNameTable.name as display_name'),
            )
            ->leftJoin(DB::raw("({$subQuery->toSql()}) as CategoryNameTable"), function ($leftJoin) {
                $leftJoin->on('cat.id', '=', DB::raw('CategoryNameTable.id'));
            })
            ->groupBy('cat.id', 'cat.code', DB::raw('CategoryNameTable.name'));

        $this->addFilter('category_name', DB::raw('CASE WHEN JSON_EXTRACT('.$tablePrefix."cat.additional_data, '$.locale_specific.".$localeCode.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(".$tablePrefix."cat.additional_data, '$.locale_specific.".$localeCode.".name'), '\"', '') ELSE CONCAT('[', ".$tablePrefix."cat.code, ']') END"));

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
            'index'      => 'display_name',
            'label'      => trans('admin::app.catalog.categories.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'category_name',
            'label'      => trans('admin::app.catalog.categories.index.datagrid.category-name'),
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
        if (! config('elasticsearch.enabled')) {
            parent::processRequest();

            return;
        }

        try {
            $params = $this->validatedRequest();

            $pagination = $params['pagination'] ?? [];
            $pagination['per_page'] ??= $this->itemsPerPage;
            $pagination['page'] ??= 1;

            $indexPrefix = config('elasticsearch.prefix');

            $results = Elasticsearch::search([
                'index' => strtolower($indexPrefix.'_categories'),
                'body'  => [
                    'from'          => ($pagination['page'] * $pagination['per_page']) - $pagination['per_page'],
                    'size'          => $pagination['per_page'],
                    'stored_fields' => [],
                    'sort'          => $this->getElasticSort($params['sort'] ?? []),
                    'query'         => [
                        'bool' => $this->getElasticFilters($params['filters'] ?? []) ?: new \stdClass,
                    ],
                ],
            ]);

            $ids = collect($results['hits']['hits'])->pluck('_id')->toArray();

            $this->queryBuilder->whereIn('cat.id', $ids)
                ->orderBy(DB::raw('FIELD('.DB::getTablePrefix().'cat.id, '.implode(',', $ids).')'));

            $total = Elasticsearch::count([
                'index' => strtolower($indexPrefix.'_categories'),
                'body'  => [
                    'query' => [
                        'bool' => $this->getElasticFilters($params['filters'] ?? []) ?: new \stdClass,
                    ],
                ],
            ])['count'];

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
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                Log::error('Elasticsearch index not found. Please create an index first.');
                parent::processRequest();

                return;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Process request.
     */
    protected function getElasticFilters(array $params): array
    {
        $filters = [];

        $localeCode = core()->getRequestedLocaleCode();

        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }

            if ($attribute == 'all') {
                $attribute = 'name';
            }

            $value = array_filter($value, function ($val) {
                return $val !== null && $val !== '';
            });

            if (count($value) > 0) {
                $filters['filter'][] = $this->getFilterValue($attribute, $value, $localeCode);
            }
        }

        return $filters;
    }

    /**
     * Return applied filters
     */
    public function getFilterValue(mixed $attribute, mixed $values, string $localeCode): array
    {
        switch ($attribute) {
            /** For Grid search filter the parameter is sent as name */
            case 'name':
                $values = current($values);

                return [
                    'bool' => [
                        'should' => [
                            [
                                'wildcard' => [
                                    'additional_data.locale_specific.'.$localeCode.'.name.keyword' => '*'.$values.'*',
                                ],
                            ], [
                                'wildcard' => [
                                    'code' => '*'.$values.'*',
                                ],
                            ],
                        ],
                    ],
                ];
            case 'category_name':
                return [
                    'terms' => [
                        'additional_data.locale_specific.'.$localeCode.'.name.keyword' => $values,
                    ],
                ];

            case 'code':
                return [
                    'terms' => [
                        'code' => $values,
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

        if ($sort == 'code') {
            $sort .= '.keyword';
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
    private function getSubQuery(string $locale, string $tablePrefix): Builder
    {
        return DB::table(DB::raw("(WITH RECURSIVE tree_view AS (
            SELECT id,
                parent_id,
                (CASE WHEN JSON_EXTRACT(additional_data, '$.locale_specific.".$locale.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(additional_data, '$.locale_specific.".$locale.".name'), '\"', '') ELSE CONCAT('[', code, ']') END) as name
            FROM ".$tablePrefix."categories
            WHERE parent_id IS NULL
            UNION ALL

            SELECT parent.id,
                parent.parent_id,
                CONCAT(tree_view.name, ' / ', (CASE WHEN JSON_EXTRACT(additional_data, '$.locale_specific.".$locale.".name') IS NOT NULL THEN REPLACE(JSON_EXTRACT(additional_data, '$.locale_specific.".$locale.".name'), '\"', '') ELSE CONCAT('[', code, ']') END)) AS name
            FROM ".$tablePrefix.'categories parent
            JOIN tree_view ON parent.parent_id = tree_view.id
        )
        SELECT id, parent_id, name
        FROM tree_view
        ) as CategoryNameTable'));
    }
}
