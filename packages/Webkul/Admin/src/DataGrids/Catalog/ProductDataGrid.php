<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Traits\AttributeColumnTrait;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\DataGrid;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;
use Webkul\Product\Factories\ElasticSearch\Cursor\ResultCursorFactory;
use Webkul\Product\Factories\ProductQueryBuilderFactory;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\AttributeValueNormalizer;
use Webkul\Product\Type\AbstractType;

class ProductDataGrid extends DataGrid implements ExportableInterface
{
    use AttributeColumnTrait;

    /**
     * Prepare query builder.
     *
     * @var object
     */
    protected $prepareQuery;

    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'product_id';

    protected $sortColumn = 'products.updated_at';

    protected $elasticSearchSortColumn = 'updated_at';

    protected $attributeColumns = [];

    protected $productQueryBuilder;

    protected $defaultColumns = [
        'sku',
        'attribute_family',
        'parent',
        'product_id',
        'status',
        'type',
    ];

    /**
     * {@inheritdoc}
     */
    protected bool $manageableColumn = true;

    /**
     * Constructor for the class.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductRepository $productRepository,
        protected ChannelRepository $channelRepository,
        protected ProductAttributeValuesNormalizer $valuesNormalizer,
        protected AttributeService $attributeService,
        protected AttributeValueNormalizer $attributeValueNormalizer,
    ) {}

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $weight = 330.0000;

        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);

        dd($formatter->format($weight));

        $tablePrefix = DB::getTablePrefix();

        $this->prepareQuery = ProductQueryBuilderFactory::make()->prepareQueryBuilder();

        $queryBuilder = $this->prepareQuery->getQueryManager();

        $queryBuilder->leftJoin('attribute_family_translations as attribute_family_name', function ($join) {
            $join->on('attribute_family_name.attribute_family_id', '=', 'af.id')
                ->where('attribute_family_name.locale', '=', core()->getRequestedLocaleCode());
        })
        ->select(
            'products.sku',
            'products.id as product_id',
            'products.status',
            'products.type',
            'products.updated_at',
            'parent_products.sku as parent',
            DB::raw('
                COALESCE(`products`.`values`, `parent_products`.`values`) as raw_values
            '),
            DB::raw('
                CASE 
                    WHEN '.$tablePrefix.'attribute_family_name.name IS NULL 
                        OR CHAR_LENGTH(TRIM('.$tablePrefix.'attribute_family_name.name)) < 1 
                    THEN CONCAT("[", '.$tablePrefix.'af.code, "]") 
                    ELSE '.$tablePrefix.'attribute_family_name.name 
                END as attribute_family
            ')
        );

        return $queryBuilder;
    }

    /**
     * Property column list.
     *
     * @return array
     */
    public function getPropertyColumns()
    {
        return [
            'sku' => [
                'index'      => 'sku',
                'label'      => trans('admin::app.catalog.products.index.datagrid.sku'),
                'type'       => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable'   => true,
            ],
            'attribute_family' => [
                'index'   => 'attribute_family',
                'label'   => trans('admin::app.catalog.products.index.datagrid.attribute-family'),
                'type'    => 'dropdown',
                'options' => [
                    'type'   => 'basic',
                    'params' => [
                        'options' => $this->attributeFamilyRepository->all(['code as label', 'id as value'])->toArray(),
                    ],
                ],
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
            'status' => [
                'index'      => 'status',
                'label'      => trans('admin::app.catalog.products.index.datagrid.status'),
                'type'       => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
                'options'    => [
                    'type'   => 'basic',
                    'params' => [
                        'options' => [
                            [
                                'label' => trans('admin::app.common.enable'),
                                'value' => 1,
                            ], [
                                'label' => trans('admin::app.common.disable'),
                                'value' => 0,
                            ],
                        ],
                    ],
                ],
                'closure' => function ($row) {
                    return $row->status
                        ? "<span class='label-active'>".trans('admin::app.common.enable').'</span>'
                        : "<span class='label-info'>".trans('admin::app.common.disable').'</span>';
                },
            ],
            'parent' => [
                'index'      => 'parent',
                'label'      => trans('admin::app.catalog.products.index.datagrid.parent'),
                'type'       => 'string',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
            'product_id' => [
                'index'      => 'product_id',
                'label'      => trans('admin::app.catalog.products.index.datagrid.id'),
                'type'       => 'integer',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
            'type' => [
                'index'   => 'type',
                'label'   => trans('admin::app.catalog.products.index.datagrid.type'),
                'type'    => 'dropdown',
                'options' => [
                    'type' => 'basic',

                    'params' => [
                        'options' => collect(config('product_types'))
                            ->map(fn ($type) => ['label' => trans($type['name']), 'value' => $type['key']])
                            ->values()
                            ->toArray(),
                    ],
                ],
                'closure'    => fn ($row) => trans('product::app.type.'.$row->type),
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
            'updated_at' => [
                'index'      => 'updated_at',
                'label'      => trans('admin::app.catalog.products.index.datagrid.updated-at'),
                'type'       => 'datetime',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
            'created_at' => [
                'index'      => 'created_at',
                'label'      => trans('admin::app.catalog.products.index.datagrid.created-at'),
                'type'       => 'datetime',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ],
        ];
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->managedColumns = request()->get('managedColumns', []);
        $this->defaultColumns = !empty($this->managedColumns)
            ? $this->managedColumns
            : $this->defaultColumns;

        $propertyColumns = $this->getPropertyColumns();

        foreach ($this->defaultColumns as $column) {
            if (! isset($propertyColumns[$column])) {
                $this->prepareAttributeColumns($column);
                $this->attributeColumns[] = $column;

                continue;
            }

            $this->addColumn($propertyColumns[$column]);
        }
    }

    public function prepareAttributeColumns($column)
    {
        $attribute = $this->attributeService->findAttributeByCode($column);

        if (! $attribute) {
            return;
        }

        $this->addColumn($this->buildColumnDefinition($attribute));
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.products.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.products.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.products.edit', $row->product_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.products.copy')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-copy',
                'title'  => trans('admin::app.catalog.products.index.datagrid.copy'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.products.copy', $row->product_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.products.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'index'  => 'delete',
                'title'  => trans('admin::app.catalog.products.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.products.delete', $row->product_id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('catalog.products.delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.products.index.datagrid.delete'),
                'url'     => route('admin.catalog.products.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }

        if (bouncer()->hasPermission('catalog.products.edit')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.products.index.datagrid.update-status'),
                'url'     => route('admin.catalog.products.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('admin::app.catalog.products.index.datagrid.active'),
                        'value' => true,
                    ],
                    [
                        'label' => trans('admin::app.catalog.products.index.datagrid.disable'),
                        'value' => false,
                    ],
                ],
            ]);
        }
    }

    /**
     * Process request.
     */
    public function processRequest(): void
    {
        if (! core()->isElasticsearchEnabled()) {
            parent::processRequest();

            return;
        }

        $requestedParams = $this->validatedRequest();

        try {
            $pagination = $requestedParams['pagination'] ?? [];
            $pagination['per_page'] ??= $this->itemsPerPage;
            $pagination['page'] ??= 1;

            $this->setElasticSort($requestedParams['sort'] ?? []);
            $this->setElasticFilters($requestedParams['filters'] ?? []);

            $esQuery = ElasticSearchQuery::build();
            $result = ResultCursorFactory::createCursor($esQuery, $requestedParams);

            $ids = $result->getAllIds();

            $this->queryBuilder->whereIn('products.id', $ids)
                ->orderBy(DB::raw('FIELD('.DB::getTablePrefix().'products.id, '.implode(',', $ids).')'));

            if (isset($requestedParams['export']) && (bool) $requestedParams['export']) {
                $this->exportable = true;

                $gridData = $this instanceof ExportableInterface ? $this->getExportableData($requestedParams) : $this->queryBuilder->get();

                $this->setExportFile($gridData, $requestedParams['format']);

                return;
            }

            $total = $result->count();

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
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        if (empty($requestedFilters)) {
            return $this->queryBuilder;
        }

        $this->processFilters($requestedFilters);

        return $this->prepareQuery->getQueryManager();
    }

    /**
     * {@inheritdoc}
     */
    public function processRequestedSorting($requestedSort)
    {
        $sortColumn = $requestedSort['column'] ?? $this->sortColumn ?? $this->primaryColumn;
        $sortOrder = $requestedSort['order'] ?? $this->sortOrder;

        if ($attributePath = $this->getAttributePathForSort($sortColumn)) {
            return $this->queryBuilder->orderByRaw(
                sprintf("JSON_EXTRACT(products.values, '%s') + 0 %s", $attributePath, $sortOrder)
            );
        }

        return $this->queryBuilder->orderBy($sortColumn, $sortOrder);
    }

    /**
     * Process request.
     */
    protected function setElasticFilters($params)
    {
        if (empty($params)) {
            return;
        }

        $this->processFilters($params);
    }

    /**
     * Processes the filters applied to the datagrid.
     */
    protected function processFilters(array $params): void
    {
        $context = [
            'locale'  => core()->getRequestedLocaleCode(),
            'channel' => core()->getRequestedChannelCode(),
        ];

        $queryBuilder = $this->prepareQuery->setQueryManager($this->queryBuilder);

        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }

            if ($attribute === 'all') {
                $queryBuilder->applySkuOrUnfilteredFilter(['sku', 'name'], FilterOperators::WILDCARD, $value, $context);

                continue;
            }

            if ($attribute === 'indices') {
                $this->applyFilterValue($queryBuilder, 'product_id', $value, FilterOperators::IN, $context);

                continue;
            }

            [$operator, $value] = $this->getOperatorAndValue($attribute, $value);
            if ($operator) {
                $this->applyFilterValue($queryBuilder, $attribute, $value, $operator, $context);
            }
        }
    }

    protected function getOperatorAndValue($attribute, $value)
    {
        $column = array_filter($this->columns, function ($column) use ($attribute) {
            return $column->index === $attribute;
        });

        if (empty($column)) {
            return;
        }

        $column = reset($column);

        switch ($column->type) {
            case 'datetime_range':
            case 'date_range':
                $operator = FilterOperators::RANGE;
                $value = current($value);
                break;
            case 'price':
                $operator = FilterOperators::EQUAL;
                $value = current($value);
                break;
            default:
                $operator = FilterOperators::IN;
                break;
        }

        return [$operator, $value];
    }

    /**
     * Return applied filters
     */
    public function applyFilterValue(mixed $queryBuilder, mixed $attribute, mixed $values, FilterOperators $operator, array $context = []): void
    {
        $queryBuilder->applyFilter($attribute, $operator, $values, $context);
    }

    /**
     * Process request.
     */
    protected function setElasticSort($params)
    {
        $sort = $params['column'] ?? $this->elasticSearchSortColumn;

        $sortMapping = [
            'type'             => 'type.keyword',
            'sku'              => 'sku.keyword',
            'attribute_family' => 'attribute_family_id',
            'product_id'       => 'id',
            'updated_at'       => 'updated_at',
            'status'           => 'status',
        ];

        $sort = $sortMapping[$sort] ?? $this->getAttributePathForSort($sort, 'elasticsearch');

        ElasticSearchQuery::orderBy([
            $sort => [
                'order'         => $params['order'] ?? $this->sortOrder,
                'missing'       => '_last',
                'unmapped_type' => 'keyword',
            ],
        ]);

    }

    /**
     *  Process request. sort order by attribute
     */
    protected function getAttributePathForSort($attributeCode, string $searchEngine = 'database')
    {
        $attribute = $this->attributeService->findAttributeByCode($attributeCode);

        if (! $attribute) {
            return null;
        }

        $locale = core()->getRequestedLocaleCode();
        $channel = core()->getRequestedChannelCode();

        $path = sprintf('$.%s.%s', $attribute->getScope($locale, $channel), $attribute->code);

        if ($searchEngine == 'elasticsearch') {
            return sprintf('values.%s.%s.keyword', $attribute->getScope($locale, $channel), $attribute->code);
        }

        return $path;
    }

    /**
     * Return formatted rows of data which can be used for exporting the data to a file
     */
    public function getExportableData(array $parameters = []): array|Collection
    {
        $this->queryBuilder = $this->queryBuilder->addSelect('products.values', 'af.code as attribute_family');

        $gridData = $this->queryBuilder->paginate(
            perPage: $parameters['pagination']['per_page'] ?? $this->itemsPerPage,
            page: $parameters['pagination']['page'] ?? 1
        );

        $exportableData = [];

        $columns = [];

        foreach ($gridData as $product) {
            $productArray = (array) $product;

            $productValues = $this->getProductValues($productArray);

            unset($productArray[AbstractType::PRODUCT_VALUES_KEY]);

            foreach ($this->getAllChannelsAndLocales() as [$channelCode, $localeCode]) {
                $data = $this->getInitialData($channelCode, $localeCode);

                $data += $this->formatProductColumnsData($productArray);

                $data += $this->formatProductValues($productValues, $localeCode, $channelCode);

                $columns = $this->getColumnsFromData($data, $columns);

                $exportableData[] = $data;
            }
        }

        return [
            'columns' => $columns,
            'records' => $exportableData,
        ];
    }

    /**
     * Format product values for quick export
     */
    protected function formatProductValues(array $productValues, string $locale, string $channel)
    {
        $values = $productValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channel][$locale] ?? [];

        $values += $productValues[AbstractType::CHANNEL_VALUES_KEY][$channel] ?? [];

        $values += $productValues[AbstractType::LOCALE_VALUES_KEY][$locale] ?? [];

        $values += $productValues[AbstractType::COMMON_VALUES_KEY] ?? [];

        $values = $this->valuesNormalizer->normalizeAttributes($values, ['forExport' => true]);

        $categories = $productValues[AbstractType::CATEGORY_VALUES_KEY] ?? [];

        $associations = $this->valuesNormalizer->normalizeAssociations($productValues[AbstractType::ASSOCIATION_VALUES_KEY] ?? []);

        return [
            ...$values,
            ...['categories' => implode(', ', $categories)],
            ...$associations,
        ];
    }

    /**
     * get channel and locales as iterable
     */
    protected function getAllChannelsAndLocales(): iterable
    {
        foreach ($this->channelRepository->all() as $channel) {
            $channelCode = $channel->code;

            foreach ($channel->locales as $locale) {
                $localeCode = $locale->code;

                yield [$channelCode, $localeCode];
            }
        }
    }

    /**
     * Add Channel and locale columns to export data
     */
    protected function getInitialData(string $channelCode, string $localeCode): array
    {
        return [
            'channel' => $channelCode,
            'locale'  => $localeCode,
        ];
    }

    /**
     * All the records columns are used to generate a total number of columns
     */
    protected function getColumnsFromData(array $data, array $columns): array
    {
        return array_unique(array_merge($columns, array_keys($data)));
    }

    /**
     * Returns product values section from the product array
     */
    protected function getProductValues(array $product): array
    {
        return ! empty($product[AbstractType::PRODUCT_VALUES_KEY])
            ? json_decode($product[AbstractType::PRODUCT_VALUES_KEY], true) ?? []
            : [];
    }

    /**
     * Set File name to be used during quick export
     */
    protected function getExportFileName(): string
    {
        return 'products';
    }

    /**
     * Process product table columns data
     */
    protected function formatProductColumnsData(array $productArray): array
    {
        $productArray['status'] = $productArray['status'] ? 'true' : 'false';

        return $productArray;
    }

    /**
     * Format data.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        /**
         * TODO: need to handle this...
         */
        foreach ($this->columns as $column) {
            $column->input_type = $column->getFormInputType();

            $column->options = $column->getFormOptions();
        }

        foreach ($paginator['data'] as $record) {
            $record = $this->sanitizeRow($record);

            foreach ($this->columns as $column) {
                if (in_array($column->index, $this->attributeColumns)) {
                    continue;
                }

                if ($closure = $column->closure) {
                    $record->{$column->index} = $closure($record);

                    $record->is_closure = true;
                }
            }

            $this->processRawValues($record);

            $record->actions = [];

            foreach ($this->actions as $index => $action) {
                $getUrl = $action->url;

                $record->actions[] = [
                    'index'         => ! empty($action->index) ? $action->index : 'action_'.$index + 1,
                    'icon'          => $action->icon,
                    'title'         => $action->title,
                    'method'        => $action->method,
                    'url'           => $getUrl($record),
                    'frontend_view' => $action?->frontendView,
                ];
            }
        }

        return [
            'id'                  => Crypt::encryptString(get_called_class()),
            'columns'             => $this->columns,
            'actions'             => $this->actions,
            'mass_actions'        => $this->massActions,
            'search_placeholder'  => __($this->searchPlaceholder),
            'records'             => $paginator['data'],
            'manageableColumn'   => $this->manageableColumn,
            'managedColumns'     => $this->managedColumns,
            'meta'                => [
                'primary_column'   => $this->primaryColumn,
                'default_order'    => $this->sortColumn,
                'from'             => $paginator['from'],
                'to'               => $paginator['to'],
                'total'            => $paginator['total'],
                'per_page_options' => [10, 20, 30, 40, 50],
                'per_page'         => $paginator['per_page'],
                'current_page'     => $paginator['current_page'],
                'last_page'        => $paginator['last_page'],
            ],
        ];
    }

    /**
     * Process raw values and update record.
     */
    protected function processRawValues(object &$record): void
    {
        if (empty($this->attributeColumns)) {
            return;
        }

        $rawValues = json_decode($record->raw_values, true);
        $values = $this->attributeValueNormalizer->normalize($rawValues, [
            'locale'                 => core()->getRequestedLocaleCode(),
            'channel'                => core()->getRequestedChannelCode(),
            'format'                 => 'datagrid',
            'processed_on_attribute' => ! empty($this->attributeColumns) ?? false,
            'attribute_codes'        => $this->attributeColumns,
        ]);

        unset($record->raw_values);

        foreach ($this->columns as $column) {
            if (! in_array($column->index, $this->attributeColumns)) {
                continue;
            }

            if ($closure = $column->closure) {
                $record->{$column->index} = $closure($values[$column->index] ?? null, $record);

                continue;
            }

            $record->{$column->index} = $values[$column->index] ?? null;
        }
    }
}
