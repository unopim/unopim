<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Database\Eloquent\Builder;
use Webkul\Product\Repositories\ProductRepository;

class ProductDataSource extends ApiDataSource
{
    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn = 'products.id';

    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository
    ) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the product repository.
     */
    public function prepareApiQueryBuilder()
    {
        [$queryBuilder] = $this->productRepository->queryBuilderFromDatabase([]);

        return $queryBuilder;
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted category field data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {
            $responseData = [
                'sku'        => $data['sku'],
                'parent'     => $data['parent']['sku'] ?? null,
                'family'     => $data['attribute_family']['code'],
                'type'       => $data['type'],
                'additional' => $data['additional'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'values'     => $data['values'],
            ];

            if (config('product_types.configurable.key') == $data['type']) {
                $superAttributes = $this->getSupperAttributes($data);
                $responseData['super_attributes'] = $superAttributes;
                $responseData['variants'] = $this->getVariants($data, $superAttributes);
            }

            return $responseData;
        }, $paginator['data'] ?? []);
    }

    /**
     * Get category field by its code.
     *
     * @param  string  $code  The unique code of the category field.
     * @return array An associative array containing the category field's code, status, and label.
     *
     * @throws ModelNotFoundException If a category field with the given code is not found.
     */
    public function getByCode(string $code)
    {
        $this->prepareForSingleData();

        $requestedFilters = [
            'sku' => [
                [
                    'operator' => '=',
                    'value'    => $code,
                ],
            ],
        ];

        $this->queryBuilder = $this->processRequestedFilters($requestedFilters);
        $product = $this->queryBuilder->first()?->toArray();

        if (! $product) {
            throw new ModelNotFoundException(
                sprintf('Product with sku %s could not be found.', (string) $code)
            );
        }

        $responseData = [
            'sku'        => $product['sku'],
            'parent'     => $product['parent']['sku'] ?? null,
            'family'     => $product['attribute_family']['code'],
            'type'       => $product['type'],
            'additional' => $product['additional'],
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at'],
            'values'     => $product['values'],
        ];

        if (config('product_types.configurable.key') == $product['type']) {
            $superAttributes = $this->getSupperAttributes($product);
            $responseData['super_attributes'] = $superAttributes;
            $responseData['variants'] = $this->getVariants($product, $superAttributes);
        }

        return $responseData;
    }

    public function getSupperAttributes($data)
    {
        if (! isset($data['super_attributes'])) {
            return [];
        }

        return array_map(function ($data) {
            return $data['code'];
        }, $data['super_attributes']);
    }

    /**
     * Retrieves variant data for a product.
     *
     * @param  array  $data  The product data containing variant information.
     * @param  array  $superAttributes  The super attributes of the product.
     * @return array
     */
    public function getVariants(array $data, array $superAttributes)
    {
        if (! isset($data['variants'])) {
            return [];
        }

        return array_map(function ($data) use ($superAttributes) {
            $variantAttributes = $this->getVariantAttributeAndOption(array_intersect($superAttributes, array_keys($data['values']['common'])), $data['values']['common']);

            return [
                'sku'        => $data['sku'],
                'attributes' => $variantAttributes,
            ];
        }, $data['variants'] ?? []);
    }

    /**
     * Retrieves variant attribute and option data for a product.the product data. The retrieved attribute and option values are then stored in a new array.
     *
     * @param  array  $superAttributes  An array of super attribute codes.
     * @param  array  $data  An array of product data containing variant information.
     * @return array An array of variant attribute and option data.
     */
    public function getVariantAttributeAndOption(array $superAttributes, array $data)
    {
        $variantAttributues = [];
        foreach ($superAttributes as $key => $value) {
            $variantAttributues[$value] = $data[$value];
        }

        return $variantAttributues;
    }

    /**
     * Applies the specified operator to the query builder based on the given column and value.
     *
     * @param  Builder  $scopeQueryBuilder  The query builder instance to apply the operator to.
     * @param  string  $requestedColumn  The column to apply the operator to.
     * @param  array  $value  The value and operator to apply.
     * @return Builder The updated query builder instance.
     */
    public function operatorByFilter($scopeQueryBuilder, $requestedColumn, $value)
    {
        $filterTable = isset($this->fieldFiltersAndOperators[$requestedColumn]['filterTable']) ? $this->fieldFiltersAndOperators[$requestedColumn]['filterTable'].'.' : 'products.';

        switch ($requestedColumn) {
            case 'parent':
                $scopeQueryBuilder->where($filterTable.'parent_id', $this->getParentIdByCode($scopeQueryBuilder, $value['value']));
                break;
            case 'family':
                $scopeQueryBuilder = $this->filterByFamily($scopeQueryBuilder, $value['operator'], $value['value']);
                break;
            case 'categories':
                $scopeQueryBuilder = $this->filterByCategories($scopeQueryBuilder, $value['operator'], $filterTable, $value['value']);
                break;
            case 'status':
                $scopeQueryBuilder->where($filterTable.'values->common->status', $value['value']);
                break;
            default:
                $scopeQueryBuilder->where($filterTable.$requestedColumn, $value['value']);
                break;
        }

        // Return the updated query builder instance.
        return $scopeQueryBuilder;

    }

    /**
     * Retrieves the ID of a product based on its code.
     *
     *
     * @return int|null
     *
     * @throws ModelNotFoundException
     *                                If a product with the given code is not found.
     */
    protected function getParentIdByCode(Builder $queryBuilder, string $sku)
    {
        $parentQuery = clone $queryBuilder;
        $parentQuery->where('products.sku', $sku);
        $parentQuery->orWhere('products.type', config('product_types.configurable.key'));
        $parentId = $parentQuery->get()->first()?->id;

        if (! $parentId) {
            throw new ModelNotFoundException(
                sprintf('Parent with sku %s could not be found.', (string) $sku)
            );
        }

        return $parentId;
    }

    /**
     * Filters the product query builder by the attribute family code.
     *
     *
     * @return Builder
     */
    protected function filterByFamily(Builder $scopeQueryBuilder, string $operator, array $code)
    {
        $scopeQueryBuilder->whereHas('attribute_family', function ($query) use ($operator, $code) {
            if ($this->operators['IN_LIST'] == $operator) {
                $query->whereIn('attribute_families.code', $code);
            } else {
                $query->whereNotIn('attribute_families.code', $code);
            }

            return $query;
        });

        return $scopeQueryBuilder;
    }

    /**
     * Filters the product query builder by the category code.
     *
     * @param  array  $code
     * @return Builder
     */
    protected function filterByCategories(Builder $scopeQueryBuilder, string $operator, string $filterTable, array $value)
    {
        if ($this->operators['IN_LIST'] == $operator) {
            $scopeQueryBuilder->whereJsonContains($filterTable.'values->categories', $value);
        } else {
            $scopeQueryBuilder->whereJsonDoesntContain($filterTable.'values->categories', $value);
        }

        return $scopeQueryBuilder;
    }
}
