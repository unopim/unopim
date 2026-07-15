<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataGrid\DataGrid;

class CategoryDataGrid extends DataGrid
{
    /**
     * Create a new datagrid instance.
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

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
    protected $sortColumn = 'category_name';

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

        $categoryNameExpr = $this->categoryNameExpression("{$tablePrefix}cat");

        $queryBuilder = DB::table('categories as cat')
            ->select(
                'cat.id as category_id',
                'cat.code as code',
                DB::raw($categoryNameExpr.' as category_name')
            );

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

            $this->attachDisplayNames();

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

            if (! empty($ids)) {
                $this->queryBuilder->whereIn('cat.id', $ids)
                    ->orderByRaw(DB::rawQueryGrammar()->orderByField(DB::getTablePrefix().'cat.id', $ids));
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

            $this->attachDisplayNames();
        } catch (\Exception $e) {
            Log::error('Elasticsearch unavailable, falling back to database query: '.$e->getMessage());
            parent::processRequest();

            $this->attachDisplayNames();

            return;
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

            $value = (array) $value;

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
        $values = (array) $values;

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
        $sort = $params['column'] ?? $this->primaryColumn;

        if ($sort == 'category_name') {
            $sort = 'name.keyword';
        }

        if ($sort == 'code') {
            $sort .= '.keyword';
        }

        if ($sort === 'category_id') {
            $sort = 'id';
        }

        return [
            $sort => [
                'order' => $params['order'] ?? $this->sortOrder,
            ],
        ];
    }

    /**
     * Build the localized category-name SQL expression for the given table alias.
     * Falls back to `[code]` when no localized name is stored for the locale.
     */
    private function categoryNameExpression(string $tableAlias): string
    {
        $grammar = DB::rawQueryGrammar();

        $localeCode = core()->getRequestedLocaleCode();

        $jsonExpr = $grammar->jsonExtract("{$tableAlias}.additional_data", 'locale_specific', $localeCode, 'name');

        $codeFallback = $grammar->concat("'['", "{$tableAlias}.code", "']'");

        return "(CASE WHEN {$jsonExpr} IS NOT NULL THEN REPLACE({$jsonExpr}, '\"', '') ELSE {$codeFallback} END)";
    }

    /**
     * Resolve the full breadcrumb path (e.g. "Root / Child / Leaf") for the
     * categories on the current page only, and assign it to `display_name`.
     *
     * The path is computed after pagination so we never walk the whole tree —
     * only the ancestors of the visible rows are read.
     */
    private function attachDisplayNames(): void
    {
        if ($this->exportable || ! isset($this->paginator)) {
            return;
        }

        $records = $this->paginator->getCollection();

        if ($records->isEmpty()) {
            return;
        }

        $ids = $records->pluck('category_id')->all();

        $breadcrumbs = $this->categoryRepository->getBreadcrumbsForIds($ids);

        $records->each(function ($record) use ($breadcrumbs) {
            $record->display_name = $breadcrumbs[(int) $record->category_id] ?? $record->category_name;
        });
    }
}
