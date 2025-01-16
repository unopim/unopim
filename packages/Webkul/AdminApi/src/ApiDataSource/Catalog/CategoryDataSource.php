<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Category\Repositories\CategoryRepository;

class CategoryDataSource extends ApiDataSource
{
    /**
     * Default sort column of data.
     *
     * @var ?string
     */
    protected $sortColumn = 'categories.id';

    const EQUALOPERATOR = '=';

    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the category repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('code', [
            '=',
            'IN',
            'NOT IN',
        ]);

        $this->addFilter('parent', [
            '=',
        ]);

        return $this->categoryRepository->queryBuilder();
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
            return [
                'code'            => $data['code'],
                'parent'          => $data['parent_category']['code'] ?? null,
                'additional_data' => $data['additional_data'],
            ];
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
    public function getByCode($code)
    {
        $this->prepareForSingleData();

        $requestedFilters = [
            'code' => [
                [
                    'operator' => '=',
                    'value'    => $code,
                ],
            ],
        ];

        $this->queryBuilder = $this->processRequestedFilters($requestedFilters);

        $category = $this->queryBuilder->first();

        if (! $category) {
            throw new ModelNotFoundException(
                sprintf('Category with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'            => $category['code'],
            'parent'          => $data['parent_category']['code'] ?? null,
            'additional_data' => $category['additional_data'],
        ];
    }

    // Delete Category by its code
    public function deleteByCode(string $code)
    {
        $this->prepareForSingleData();
        $requestedFilters = [
            'code' => [
                [
                    'operator' => self::EQUALOPERATOR,
                    'value'    => $code,
                ],
            ],
        ];
        $category = $this->processRequestedFilters($requestedFilters)->first();
        if (! $category) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.categories.not-found', ['code' => (string) $code])
            );
        }
        $category->delete();

        return [
            'message' => trans('admin::app.catalog.categories.delete-success'),
            'code'    => $category['code'],
        ];
    }

    /**
     * Applies the specified operator to the query builder based on the given column and value.
     *
     * @param  \Illuminate\Database\Query\Builder  $scopeQueryBuilder  The query builder instance to apply the operator to.
     * @param  string  $requestedColumn  The column to apply the operator to.
     * @param  array  $value  The value and operator to apply.
     * @return \Illuminate\Database\Query\Builder The updated query builder instance.
     */
    public function operatorByFilter($scopeQueryBuilder, $requestedColumn, $value)
    {
        $filterTable = isset($this->fieldFiltersAndOperators[$requestedColumn]['filterTable']) ? $this->fieldFiltersAndOperators[$requestedColumn]['filterTable'].'.' : 'categories.';

        if ($this->operators['EQUALS'] == $value['operator']) {
            // Apply the 'equals' operator to the query builder.
            if ($requestedColumn == 'parent') {
                $scopeQueryBuilder->where($filterTable.'parent_id', $this->getParentIdByCode($scopeQueryBuilder, $value['value']));
            } else {
                $scopeQueryBuilder->where($filterTable.$requestedColumn, $value['value']);
            }
        }

        if ($this->operators['IN_LIST'] == $value['operator']) {
            // Apply the 'in list' operator to the query builder.
            $scopeQueryBuilder->whereIn($filterTable.$requestedColumn, $value['value']);
        }

        if ($this->operators['NOT_IN_LIST'] == $value['operator']) {
            // Apply the 'not in list' operator to the query builder.
            $scopeQueryBuilder->hereNotIn($filterTable.$requestedColumn, $value['value']);
        }

        // Return the updated query builder instance.
        return $scopeQueryBuilder;
    }

    /**
     * Retrieves the ID of a product based on its code.
     *
     * @param \Illuminate\Database\Query\Builder
     * @return int|null The ID of the product if found, otherwise null.
     */
    private function getParentIdByCode($queryBuilder, string $code)
    {
        $query = clone $queryBuilder;

        $parentId = $query->where('categories.code', $code)->get()->first()?->id;

        if (! $parentId) {
            throw new ModelNotFoundException(
                sprintf('Category with code %s could not be found.', (string) $code)
            );
        }

        return $parentId;
    }
}
