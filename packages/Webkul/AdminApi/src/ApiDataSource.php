<?php

namespace Webkul\AdminApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webkul\AdminApi\Checker\QueryParametersChecker;

abstract class ApiDataSource
{
    /**
     * set filter column and filter by operator
     */
    protected array $fieldFiltersAndOperators = [];

    /**
     * Primary column.
     */
    protected string $primaryColumn = 'id';

    /**
     * Default sort column of datagrid.
     */
    protected ?string $sortColumn = null;

    /**
     * Default filter operators.
     */
    protected array $operators = [
        'EQUALS'      => '=',
        'IN_LIST'     => 'IN',
        'NOT_IN_LIST' => 'NOT IN',
    ];

    /**
     * Default sort order of datagrid.
     */
    protected string $sortOrder = 'asc';

    /**
     * Default items per page.
     */
    protected int $itemsPerPage = 10;

    /**
     * Query builder instance.
     */
    protected object $queryBuilder;

    /**
     * Paginator instance.
     */
    protected LengthAwarePaginator $paginator;

    /**
     * Prepare query builder.
     */
    abstract public function prepareApiQueryBuilder(): mixed;

    /**
     * format Data.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return $paginator['data'];
    }

    /**
     * Map your filter.
     */
    public function addFilter(string $column, mixed $operators, ?string $filterTable = null): void
    {
        $this->fieldFiltersAndOperators[$column]['operators'] = $operators;
        if ($filterTable) {
            $this->fieldFiltersAndOperators[$column]['filterTable'] = $filterTable;
        }
    }

    /**
     * Set query builder.
     */
    public function setQueryBuilder(mixed $queryBuilder = null): void
    {
        $this->queryBuilder = $queryBuilder ?: $this->prepareApiQueryBuilder();
    }

    /**
     * Process all requested filters.
     *
     * @return Builder
     */
    public function processRequestedFilters(array $requestedFilters): mixed
    {
        return $this->queryBuilder->scopeQuery(function (mixed $scopeQueryBuilder) use ($requestedFilters) {
            $this->setDefaultFilters($scopeQueryBuilder);

            foreach ($requestedFilters as $requestedColumn => $requestedValues) {
                foreach ($requestedValues as $value) {
                    $scopeQueryBuilder = $this->operatorByFilter($scopeQueryBuilder, $requestedColumn, $value);
                }
            }

            return $scopeQueryBuilder;
        });
    }

    public function setDefaultFilters(mixed $queryBuilder): mixed
    {
        return $queryBuilder;
    }

    /**
     * Validates filter criteria and returns the parsed filter parameters.
     *
     * @param  array  $requestedParams  The request parameters containing the 'filters' key.
     * @return array The parsed filter parameters.
     *
     * @throws UnprocessableEntityHttpException If the 'filters' key is missing in the request parameters.
     */
    public function validateFilterCriterias(array $requestedParams): array
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
    public function validateFilterParameters(array $filterParameters): void
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
     * @param  Builder  $scopeQueryBuilder  The query builder instance to apply the operator to.
     * @param  string  $requestedColumn  The column to apply the operator to.
     * @param  array  $value  The value and operator to apply.
     * @return Builder The updated query builder instance.
     */
    public function operatorByFilter(mixed $scopeQueryBuilder, string $requestedColumn, array $value): mixed
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
     * @return Builder
     */
    public function processRequestedSorting(mixed $requestedSort): mixed
    {
        if (! $this->sortColumn) {
            $this->sortColumn = $this->primaryColumn;
        }

        return $this->queryBuilder->orderBy($this->sortColumn, $this->sortOrder);
    }

    /**
     * Process requested pagination.
     */
    public function processRequestedPagination(array $requestedPagination): LengthAwarePaginator
    {
        return $this->queryBuilder->paginate(
            $requestedPagination['limit'] ?? $this->itemsPerPage,
            ['*']
        );
    }

    /**
     * Process requested pagination.
     */
    public function processRequestedSingleData(): object
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
    public function getTranslations(array $data, string $labelKey = 'name'): array
    {
        if (empty($data['translations'])) {
            return [];
        }

        return array_reduce($data['translations'], function (mixed $carry, mixed $item) use ($labelKey) {
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
    public function toJson(): JsonResponse
    {
        $this->prepare();

        return response()->json($this->responseFormatData());
    }
}
