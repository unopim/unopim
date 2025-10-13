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
        $tablePrefix = DB::getTablePrefix();
        $localeCode = core()->getRequestedLocaleCode();
        $driver = DB::getDriverName();

        $subQuery = $this->getSubQuery($localeCode, $tablePrefix, $driver);

        // Choose SQL depending on driver
        switch ($driver) {
            case 'mysql':
                $categoryNameExpr = "(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT({$tablePrefix}cat.additional_data, '$.locale_specific.{$localeCode}.name')) IS NOT NULL 
                        THEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT({$tablePrefix}cat.additional_data, '$.locale_specific.{$localeCode}.name')), '\"', '') 
                        ELSE CONCAT('[', {$tablePrefix}cat.code, ']') END)";
                break;

            case 'pgsql':
                $categoryNameExpr = "(CASE WHEN {$tablePrefix}cat.additional_data->'locale_specific'->'{$localeCode}'->>'name' IS NOT NULL 
                        THEN {$tablePrefix}cat.additional_data->'locale_specific'->'{$localeCode}'->>'name'
                        ELSE '[' || {$tablePrefix}cat.code || ']' END)";
                break;

            default:
                throw new \Exception("Unsupported driver: {$driver}");
        }

        $queryBuilder = DB::table('categories as cat')
            ->select(
                'cat.id as category_id',
                'cat.code as code',
                DB::raw($categoryNameExpr.' as category_name'),
                DB::raw('category_display_names.name as display_name')
            )
            ->leftJoin(DB::raw("({$subQuery}) as category_display_names"), function ($leftJoin) {
                $leftJoin->on('cat.id', '=', DB::raw('category_display_names.id'));
            })
            ->groupBy('cat.id', 'cat.code', DB::raw('category_display_names.name'));

        $this->addFilter('category_name', DB::raw($categoryNameExpr));

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
        if (bouncer()->hasPermission('catalog.categories.mass_delete')) {
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

            $results = ElasticSearch::search([
                'index' => strtolower($indexPrefix.'_categories'),
                'body'  => [
                    'from'          => ($pagination['page'] * $pagination['per_page']) - $pagination['per_page'],
                    'size'          => $pagination['per_page'],
                    'stored_fields' => [],
                    'sort'          => $this->getElasticSort($params['sort'] ?? []),
                    'query'         => [
                        'bool' => $this->getElasticFilters($params['filters'] ?? []) ?: new \stdClass,
                    ],
                    'track_total_hits' => true,
                ],
            ]);

            $ids = collect($results['hits']['hits'])->pluck('_id')->toArray();

            $driver = DB::getDriverName();

            if (! empty($ids)) {
                if ($driver === 'mysql') {
                    $this->queryBuilder->whereIn('cat.id', $ids)
                        ->orderByRaw('FIELD('.DB::getTablePrefix().'cat.id, '.implode(',', $ids).')');
                } elseif ($driver === 'pgsql') {
                    $idList = implode(',', $ids);
                    $this->queryBuilder->whereIn('cat.id', $ids)
                        ->orderByRaw("array_position(ARRAY[{$idList}]::int[], cat.id)");
                }
            }

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
        $driver = DB::getDriverName();

        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }

            if ($attribute == 'all') {
                $attribute = 'name';
            }

            switch ($driver) {
                case 'pgsql':
                    $value = array_map(function ($val) {
                        if ($val instanceof \Illuminate\Database\Query\Expression) {
                            return (string) $val->getValue(DB::connection()->getQueryGrammar());
                        }

                        return $val;
                    }, (array) $value);
                    break;

                case 'mysql':
                default:
                    $value = (array) $value;
                    break;
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
        $driver = DB::getDriverName();

        // Normalize values depending on DB driver
        switch ($driver) {
            case 'pgsql':
                $values = array_map(function ($val) {
                    if ($val instanceof \Illuminate\Database\Query\Expression) {
                        return (string) $val->getValue(DB::connection()->getQueryGrammar());
                    }

                    return $val;
                }, (array) $values);
                break;

            case 'mysql':
            default:
                $values = (array) $values;
                break;
        }

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
                $values = current($values);
                $escaped = preg_replace('/([+\-&|!(){}\[\]^"~*?:\\\\\/])/', '\\\\$1', $values);

                $escaped = str_contains($values, ' ') ? '"*'.$escaped.'*"' : '*'.$escaped.'*';

                return [
                    'bool' => [
                        'should' => [
                            'query_string' => [
                                'fields' => [
                                    'additional_data.locale_specific.'.$localeCode.'.name',
                                    'code',
                                ],
                                'query' => $escaped,
                            ],
                        ],
                    ],
                ];

            case 'code':
                return [
                    'wildcard' => [
                        'code' => '*'.current($values).'*',
                    ],
                ];

            default:
                return [
                    'wildcard' => [
                        $attribute => '*'.current($values).'*',
                    ],
                ];
        }
    }

    /**
     * Process request.
     */
    protected function getElasticSort($params): array
    {
        $driver = DB::getDriverName();

        $sort = $params['column'] ?? $this->primaryColumn;

        switch ($driver) {
            case 'pgsql':
                // Convert Expression to string if needed
                if ($sort instanceof \Illuminate\Database\Query\Expression) {
                    $sort = (string) $sort->getValue(DB::connection()->getQueryGrammar());
                }

                // Map column aliases for Postgres
                if ($sort === 'category_name') {
                    $sort = 'name.keyword';
                } elseif ($sort === 'code') {
                    $sort = 'code.keyword';
                } elseif ($sort === 'category_id') {
                    $sort = 'id';
                }
                break;

            case 'mysql':
            default:
                if ($sort == 'category_name') {
                    $sort = 'name.keyword';
                }

                if ($sort == 'code') {
                    $sort .= '.keyword';
                }

                if ($sort === 'category_id') {
                    $sort = 'id';
                }
                break;
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
    private function getSubQuery(string $locale, string $tablePrefix, string $driver): string
    {
        switch ($driver) {
            case 'mysql':
                return "WITH RECURSIVE tree_view AS (
                    SELECT id,
                        parent_id,
                        (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(additional_data, '$.locale_specific.{$locale}.name')) IS NOT NULL 
                            THEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(additional_data, '$.locale_specific.{$locale}.name')), '\"', '') 
                            ELSE CONCAT('[', code, ']') END) as name
                    FROM {$tablePrefix}categories
                    WHERE parent_id IS NULL
                    UNION ALL
                    SELECT parent.id,
                        parent.parent_id,
                        CONCAT(tree_view.name, ' / ', 
                            (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(parent.additional_data, '$.locale_specific.{$locale}.name')) IS NOT NULL 
                                THEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(parent.additional_data, '$.locale_specific.{$locale}.name')), '\"', '') 
                                ELSE CONCAT('[', parent.code, ']') END)) AS name
                    FROM {$tablePrefix}categories parent
                    JOIN tree_view ON parent.parent_id = tree_view.id
                )
                SELECT id, parent_id, name FROM tree_view";

            case 'pgsql':
                return "WITH RECURSIVE tree_view AS (
                    SELECT id,
                        parent_id,
                        (CASE WHEN additional_data->'locale_specific'->'{$locale}'->>'name' IS NOT NULL 
                            THEN additional_data->'locale_specific'->'{$locale}'->>'name'
                            ELSE '[' || code || ']' END) as name
                    FROM {$tablePrefix}categories
                    WHERE parent_id IS NULL
                    UNION ALL
                    SELECT parent.id,
                        parent.parent_id,
                        tree_view.name || ' / ' || 
                        (CASE WHEN parent.additional_data->'locale_specific'->'{$locale}'->>'name' IS NOT NULL 
                            THEN parent.additional_data->'locale_specific'->'{$locale}'->>'name'
                            ELSE '[' || parent.code || ']' END) as name
                    FROM {$tablePrefix}categories parent
                    JOIN tree_view ON parent.parent_id = tree_view.id
                )
                SELECT id, parent_id, name FROM tree_view";

            default:
                throw new \Exception("Unsupported driver: {$driver}");
        }
    }
}
