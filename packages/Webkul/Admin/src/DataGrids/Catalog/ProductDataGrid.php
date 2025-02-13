<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataGrid\Column;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\DataGrid;
use Webkul\ElasticSearch\Facades\SearchQuery;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\ElasticSearch\Cursor\ResultCursorFactory;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\Product\Query\ProductQueryBuilder;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\AttributeValueNormalizer;
use Webkul\Product\Type\AbstractType;
use Webkul\Admin\Traits\AttributeColumnTrait;

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

    protected $sortColumn = 'updated_at';

    protected $dynamicColumns = ['gallery3', 'multiselect', 'color', 'size',  'datetime', 'price'];

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
        protected ProductQueryBuilder $productQueryBuilder,
        protected AttributeValueNormalizer $attributeValueNormalizer,
    ) {}

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $this->prepareQuery = $this->productQueryBuilder->prepareQueryBuilder();

        $queryBuilder = $this->prepareQuery->getQueryBuilder();

        $queryBuilder->leftJoin('attribute_family_translations as attribute_family_name', function ($join) {
            $join->on('attribute_family_name.attribute_family_id', '=', 'af.id')
                ->where('attribute_family_name.locale', '=', core()->getRequestedLocaleCode());
        })
            ->select(
                'products.sku',
                'products.id as product_id',
                'products.status',
                'products.type',
                'parent_products.sku as parent',
                DB::raw('
                    JSON_MERGE(
                        COALESCE(`parent_products`.`values`, "{}"),
                        COALESCE(`products`.`values`, "{}")
                    ) as raw_values
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

        $this->addFilter('product_id', 'products.id');
        $this->addFilter('attribute_family', 'af.id');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('status', 'products.status');
        $this->addFilter('type', 'products.type');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('admin::app.catalog.products.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'   => 'attribute_family',
            'label'   => trans('admin::app.catalog.products.index.datagrid.attribute-family'),
            'type'    => 'dropdown',
            'options' => [
                'type' => 'basic',

                'params' => [
                    'options' => $this->attributeFamilyRepository->all(['code as label', 'id as value'])->toArray(),
                ],
            ],
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'parent',
            'label'      => trans('admin::app.catalog.products.index.datagrid.parent'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_id',
            'label'      => trans('admin::app.catalog.products.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('admin::app.catalog.products.index.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type' => 'basic',

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
        ]);

        $this->addColumn([
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
        ]);

        $this->prepareAttributeColumns();
    }

    public function prepareAttributeColumns()
    {
        foreach ($this->dynamicColumns as $attributeCode) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            $column = $this->buildColumnDefinition($attribute);
            $this->addAttributeColumn($column);
        }
    }

    /**
     * Add column.
     */
    public function addAttributeColumn(array $column): void
    {
        $this->customColumns[] = new Column(
            index: $column['index'],
            label: $column['label'],
            type: $column['type'],
            options: $column['options'] ?? null,
            searchable: $column['searchable'],
            filterable: $column['filterable'],
            sortable: $column['sortable'],
            closure: $column['closure'] ?? null,
        );
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
        if (! env('ELASTICSEARCH_ENABLED', false)) {
            parent::processRequest();

            return;
        }

        $requestedParams = $this->validatedRequest();

        try {
            $params = $this->validatedRequest();
            $pagination = $params['pagination'];

            $this->setElasticSort($params['sort'] ?? []);
            $this->setElasticFilters($params['filters'] ?? []);

            $esQuery = SearchQuery::getQuery();

            $result = ResultCursorFactory::createCursor($esQuery, $params);

            $ids = $result->getIds();

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
    protected function setElasticFilters($params)
    {
        $context = [
            'locale'  => core()->getRequestedLocaleCode(),
            'channel' => core()->getRequestedChannelCode(),
        ];

        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }

            if ($attribute == 'all') {
                $attribute = 'name';
                $operator = Operators::CONTAINS;
            } else {
                [$operator, $value] = $this->getOperatorAndValue($attribute, $value);
            }

            $this->addFilterValue($attribute, $value, $operator, $context);
        }
    }

    protected function getOperatorAndValue($attribute, $value)
    {
        $column = array_filter(array_merge($this->columns, $this->customColumns), function ($column) use ($attribute) {
            return $column->index === $attribute;
        });

        if (empty($column)) {
            throw new \LogicException(sprintf('Unsupported column name: %s for filter', $attribute));
        }

        $column = reset($column);

        switch ($column->type) {
            case 'datetime_range':
            case 'date_range':
                $operator = Operators::BETWEEN;
                $value = current($value);
                break;
            case 'price':
                $operator = Operators::EQUALS;
                $value = current($value);
                break;
            default:
                $operator = Operators::IN_LIST;
                break;
        }

        return [$operator, $value];
    }

    /**
     * Return applied filters
     */
    public function addFilterValue(mixed $attribute, mixed $values, string $operator, array $context = []): void
    {
        $queryBuilder = $this->prepareQuery->setQueryBuilder($this->queryBuilder);

        $queryBuilder->addFilter($attribute, $operator, $values, $context);
    }

    /**
     * Process request.
     */
    protected function setElasticSort($params)
    {
        $sort = $params['column'] ?? $this->sortColumn;

        $sortMapping = [
            'type'             => 'type.keyword',
            'sku'              => 'sku.keyword',
            'attribute_family' => 'attribute_family_id',
            'product_id'       => 'id',
            'updated_at'       => 'updated_at',
            'status'           => 'status',
        ];

        $sort = $sortMapping[$sort] ?? $this->getElasticRawValuesSort($sort);

        SearchQuery::addSort([
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
    protected function getElasticRawValuesSort($attribute): string
    {
        $attribute = $this->attributeService->findAttributeByCode($attribute);

        if (! $attribute) {
            return $attribute;
        }

        return sprintf('values.%s.%s.keyword', $attribute->getScope(), $attribute->code);
    }

    /**
     * Process request.
     */
    protected function sortAttributeFamilyByKey($params): array
    {
        $sort = $params['column'] ?? $this->primaryColumn;

        if ($sort == 'attribute_family') {
            $sort = 'attribute_family_id';
            $sort .= '.keyword';
        }

        return [
            $sort => [
                'order' => $params['order'] ?? $this->sortOrder,
            ],
        ];
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
            'id'                 => Crypt::encryptString(get_called_class()),
            'columns'            => array_merge($this->columns, $this->customColumns),
            'actions'            => $this->actions,
            'mass_actions'       => $this->massActions,
            'search_placeholder' => __($this->searchPlaceholder),
            'records'            => $paginator['data'],
            'meta'               => [
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
        $rawValues = json_decode($record->raw_values, true);
        $values = $this->attributeValueNormalizer->normalize($rawValues, [
            'locale'                 => core()->getRequestedLocaleCode(),
            'channel'                => core()->getRequestedChannelCode(),
            'format'                 => 'datagrid',
            'processed_on_attribute' => $this->dynamicColumns,
        ]);

        unset($record->raw_values);

        foreach ($this->customColumns as $column) {
            if ($closure = $column->closure) {
                $record->{$column->index} = $closure($values[$column->index] ?? null, $record);

                continue;
            }

            $record->{$column->index} = $values[$column->index] ?? null;
        }
    }
}
