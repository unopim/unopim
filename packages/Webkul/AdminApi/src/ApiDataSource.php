<?php

namespace Webkul\AdminApi;

use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webkul\AdminApi\Checker\QueryParametersChecker;

abstract class ApiDataSource
{
    /**
     * set filter column and filter by operator
     *
     * @var array
     */
    protected $fieldFiltersAndOperators = [];

    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn;

    /**
     * Default filter operators.
     *
     * @var array
     */
    protected $operators = [
        'EQUALS'      => '=',
        'IN_LIST'     => 'IN',
        'NOT_IN_LIST' => 'NOT IN',
    ];

    /**
     * Default sort order of datagrid.
     *
     * @var string
     */
    protected $sortOrder = 'asc';

    /**
     * Default items per page.
     *
     * @var int
     */
    protected $itemsPerPage = 10;

    /**
     * Query builder instance.
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * Paginator instance.
     */
    protected LengthAwarePaginator $paginator;

    /**
     * Prepare query builder.
     */
    abstract public function prepareApiQueryBuilder();

    /**
     * format Data.
     */
    public function formatData()
    {
        $paginator = $this->paginator->toArray();

        return $paginator['data'];
    }

    /**
     * Map your filter.
     */
    public function addFilter(string $column, mixed $operators, $filterTable = null): void
    {
        $this->fieldFiltersAndOperators[$column]['operators'] = $operators;
        if ($filterTable) {
            $this->fieldFiltersAndOperators[$column]['filterTable'] = $filterTable;
        }
    }

    /**
     * Set query builder.
     *
     * @param  mixed  $queryBuilder
     */
    public function setQueryBuilder($queryBuilder = null): void
    {
        $this->queryBuilder = $queryBuilder ?: $this->prepareApiQueryBuilder();
    }

    /**
     * Process all requested filters.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        return $this->queryBuilder->scopeQuery(function ($scopeQueryBuilder) use ($requestedFilters) {
            $this->setDefaultFilters($scopeQueryBuilder);

            foreach ($requestedFilters as $requestedColumn => $requestedValues) {
                $scopeQueryBuilder->where(function ($query) use ($requestedValues, $requestedColumn) {
                    foreach ($requestedValues as $value) {
                        $query = $this->operatorByFilter($query, $requestedColumn, $value);
                    }
                });
            }

            return $scopeQueryBuilder;
        });
    }

    public function setDefaultFilters($queryBuilder)
    {
        return $queryBuilder;
    }

    /**
     * Validates filter criterias and returns the parsed filter parameters.
     *
     * @param  array  $requestedParams  The request parameters containing the 'filters' key.
     * @return array The parsed filter parameters.
     *
     * @throws UnprocessableEntityHttpException If the 'filters' key is missing in the request parameters.
     */
    public function validateFilterCriterias($requestedParams)
    {
        if (! isset($requestedParams['filters'])) {
            return [];
        }

        $filterParameters = QueryParametersChecker::checkCriterionParameters($requestedParams['filters']);

        $this->validateFilterParameters($filterParameters);

        return $filterParameters;
    }

    /**
     * Validates the filter parameters based on the defined criteria.
     *
     * @param  array  $filterParameters  The filter parameters to validate.
     *
     * @throws UnprocessableEntityHttpException If a filter parameter is not supported, has an unsupported operator, or lacks a value.
     */
    public function validateFilterParameters($filterParameters)
    {
        foreach ($filterParameters as $filterKey => $filterParameter) {
            foreach ($filterParameter as $filterOperator) {
                if (! in_array($filterKey, array_keys($this->fieldFiltersAndOperators))
                    || ! in_array($filterOperator['operator'], $this->fieldFiltersAndOperators[$filterKey]['operators'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter on property "%s" is not supported or does not support operator "%s".',
                            $filterKey,
                            $filterOperator['operator']
                        )
                    );
                }

                if (! isset($filterOperator['value'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf('Value is missing for the property "%s".', $filterKey)
                    );
                }

                if (
                    $this->operators['EQUALS'] == $filterOperator['operator']
                    && is_array($filterOperator['value'])
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter "%s" with operator "%s" is not supported or does not support a array value.',
                            $filterKey,
                            $filterOperator['operator']
                        )
                    );
                }

                if ((
                    $this->operators['IN_LIST'] == $filterOperator['operator']
                    || $this->operators['NOT_IN_LIST'] == $filterOperator['operator'])
                    && ! is_array($filterOperator['value'])
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter "%s" with operator "%s" expects a array value.',
                            $filterKey,
                            $filterOperator['operator']
                        )
                    );
                }

                // @TODO: Need to develop for operator value
                // if (!is_bool($filterOperator['value'])) {
                //     throw new UnprocessableEntityHttpException(
                //         sprintf(
                //             'Filter "%s" with operator "%s" expects a boolean value.',
                //             $filterKey,
                //             $filterOperator['operator']
                //         )
                //     );
                // }
            }
        }
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
        if ($this->operators['EQUALS'] == $value['operator']) {
            // Apply the 'equals' operator to the query builder.
            $scopeQueryBuilder->orWhere($requestedColumn, $value['value']);
        }

        if ($this->operators['IN_LIST'] == $value['operator']) {
            // Apply the 'in list' operator to the query builder.
            $scopeQueryBuilder->orWhereIn($requestedColumn, $value['value']);
        }

        if ($this->operators['NOT_IN_LIST'] == $value['operator']) {
            // Apply the 'not in list' operator to the query builder.
            $scopeQueryBuilder->orWhereNotIn($requestedColumn, $value['value']);
        }

        // Return the updated query builder instance.
        return $scopeQueryBuilder;
    }

    /**
     * Process requested sorting.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function processRequestedSorting($requestedSort)
    {
        if (! $this->sortColumn) {
            $this->sortColumn = $this->primaryColumn;
        }

        return $this->queryBuilder->orderBy($this->sortColumn, $this->sortOrder);
    }

    /**
     * Process requested pagination.
     */
    public function processRequestedPagination($requestedPagination)
    {
        return $this->queryBuilder->paginate(
            $requestedPagination['limit'] ?? $this->itemsPerPage,
            ['*']
        );
    }

    /**
     * Process requested pagination.
     */
    public function processRequestedSingleData()
    {
        return $this->queryBuilder;
    }

    /**
     * Process request.
     */
    public function processRequest(): void
    {
        /**
         * Store all request parameters in this variable; avoid using direct request helpers afterward.
         */
        $requestedParams = request()->only(['filters', 'sort', 'limit', 'page']);

        $requestedFiltersParams = $this->validateFilterCriterias($requestedParams);

        $this->queryBuilder = $this->processRequestedFilters($requestedFiltersParams);

        $this->queryBuilder = $this->processRequestedSorting($requestedParams['sort'] ?? []);

        $this->paginator = $this->processRequestedPagination($requestedParams);
    }

    /**
     * Process request for single data.
     */
    public function processRequestForSingleData(): void
    {
        $this->queryBuilder = $this->processRequestedSingleData();
    }

    /**
     * Get translations of the channel.
     *
     * @param  array  $channel  The channel data from the database.
     * @return array An associative array containing the locales as keys and their corresponding channel names as values.
     */
    public function getTranslations($data, $labelKey = 'name')
    {
        if (empty($data['translations'])) {
            return [];
        }

        return array_reduce($data['translations'], function ($carry, $item) use ($labelKey) {
            if (isset($item[$labelKey]) && ! empty($item[$labelKey])) {
                $carry[$item['locale']] = $item[$labelKey];
            }

            return $carry;
        }) ?? [];
    }

    /**
     * Format data.
     */
    public function responseFormatData(): array
    {
        $paginator = $this->paginator->toArray();

        return [
            'data'         => $this->formatData(),
            'current_page' => $paginator['current_page'],
            'last_page'    => $paginator['last_page'],
            'total'        => $paginator['total'],
            'links'        => [
                'first' => $paginator['first_page_url'] ?? null,
                'last'  => $paginator['last_page_url'] ?? null,
                'next'  => $paginator['next_page_url'] ?? null,
                'prev'  => $paginator['prev_page_url'] ?? null,
            ],
        ];
    }

    /**
     * Prepare all the setup for datagrid.
     */
    public function prepare(): void
    {
        $this->setQueryBuilder();

        $this->processRequest();
    }

    /**
     * Prepare all the setup for datagrid.
     */
    public function prepareForSingleData(): void
    {
        $this->setQueryBuilder();
    }

    /**
     * To json.
     */
    public function toJson()
    {
        $this->prepare();

        return response()->json($this->responseFormatData());
    }
}
