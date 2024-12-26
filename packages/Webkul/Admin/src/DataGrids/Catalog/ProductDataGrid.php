<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\DataGrid;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

class ProductDataGrid extends DataGrid implements ExportableInterface
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'product_id';

    /**
     * Constructor for the class.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductRepository $productRepository,
        protected ChannelRepository $channelRepository,
        protected ProductAttributeValuesNormalizer $valuesNormalizer
    ) {}

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('products')
            ->leftJoin('attribute_families as af', 'products.attribute_family_id', '=', 'af.id')
            ->leftJoin('products as parent_products', 'products.parent_id', '=', 'parent_products.id')
            ->leftJoin('attribute_family_translations as attribute_family_name', function ($join) {
                $join->on('attribute_family_name.attribute_family_id', '=', 'af.id')
                    ->where('attribute_family_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->select(
                'products.sku',
                'products.id as product_id',
                'products.status',
                'products.type',
                'parent_products.sku as parent',
                DB::raw('(CASE WHEN '.$tablePrefix.'attribute_family_name.name IS NULL OR CHAR_LENGTH(TRIM('.$tablePrefix.'attribute_family_name.name)) < 1 THEN CONCAT("[", '.$tablePrefix.'af.code,"]") ELSE '.$tablePrefix.'attribute_family_name.name END) as attribute_family')
            );

        $this->addFilter('product_id', 'products.id');
        $this->addFilter('attribute_family', 'af.code');
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
                    'options' => $this->attributeFamilyRepository->all(['code as label', 'code as value'])->toArray(),
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

        // Additional logic specific to ProductDataGrid
        $params = $this->validatedRequest();
        $pagination = $params['pagination'];
        $channelCodes = request()->input('filters.channel') ?? core()->getAllChannels()->pluck('code')->toArray();

        $results = Elasticsearch::search([
            'index' => strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index'),
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

        $this->queryBuilder->whereIn('products.id', $ids)
            ->orderBy(DB::raw('FIELD(products.id, '.implode(',', $ids).')'));

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
            case 'product_id':
                return [
                    'terms' => [
                        'id' => $values,
                    ],
                ];

            case 'attribute_family':
                return [
                    'terms' => [
                        'attribute_family_id' => $values,
                    ],
                ];

            case 'sku':
            case 'name':
                $filters = [];

                foreach ($values as $value) {
                    $filters['bool']['should'][] = [
                        'match_phrase_prefix' => [
                            $attribute => $value,
                        ],
                    ];
                }

                return $filters;

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

        if ($sort == 'type') {
            $sort .= '.keyword';
        }

        if ($sort == 'name') {
            $sort .= '.keyword';
        }

        if ($sort == 'attribute_family') {
            $sort .= '_id';
        }

        if ($sort == 'product_id') {
            $sort = 'id';
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
}
