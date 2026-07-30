<?php

namespace Webkul\AdminApi;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webkul\AdminApi\Cache\StructureCache;
use Webkul\AdminApi\Checker\QueryParametersChecker;
use Webkul\Core\Eloquent\Repository;

abstract class ApiDataSource
{
    public const PAGINATION_SEARCH_AFTER = 'search_after';

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
        'EQUALS'                => '=',
        'IN_LIST'               => 'IN',
        'NOT_IN_LIST'           => 'NOT IN',
        'GREATER_THAN'          => '>',
        'GREATER_THAN_OR_EQUAL' => '>=',
        'LESS_THAN'             => '<',
        'LESS_THAN_OR_EQUAL'    => '<=',
        'BETWEEN'               => 'BETWEEN',
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
     * Maximum items a client may request per page.
     */
    protected int $maxItemsPerPage = 100;

    /**
     * Query builder instance.
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * Paginator instance.
     */
    protected LengthAwarePaginator|Paginator $paginator;

    /**
     * Keyset cursor for search_after pagination; null on the first page.
     */
    protected ?int $searchAfter = null;

    /**
     * Whether the current request paginates by keyset cursor instead of page offset.
     */
    protected bool $useCursorPagination = false;

    /**
     * StructureCache group for this data source's list responses; null disables caching.
     * Only slow-changing structure entities may set this — never products.
     */
    protected ?string $structureCacheGroup = null;

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
    public function addFilter(string $column, mixed $operators, $filterTable = null, ?string $type = null): void
    {
        $this->fieldFiltersAndOperators[$column]['operators'] = $operators;
        if ($filterTable) {
            $this->fieldFiltersAndOperators[$column]['filterTable'] = $filterTable;
        }
        if ($type) {
            $this->fieldFiltersAndOperators[$column]['type'] = $type;
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
     * @return Repository
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        return $this->queryBuilder->scopeQuery(function ($scopeQueryBuilder) use ($requestedFilters) {
            $this->setDefaultFilters($scopeQueryBuilder);

            foreach ($requestedFilters as $requestedColumn => $requestedValues) {
                foreach ($requestedValues as $value) {
                    $scopeQueryBuilder = $this->operatorByFilter($scopeQueryBuilder, $requestedColumn, $value);
                }
            }

            if ($this->searchAfter !== null) {
                $scopeQueryBuilder->where($this->cursorColumn(), '>', $this->searchAfter);
            }

            return $scopeQueryBuilder;
        });
    }

    public function setDefaultFilters($queryBuilder)
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

                if (
                    in_array($filterOperator['operator'], $this->comparisonOperators(), true)
                    && is_array($filterOperator['value'])
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter "%s" with operator "%s" does not support a array value.',
                            $filterKey,
                            $filterOperator['operator']
                        )
                    );
                }

                if (
                    $this->operators['BETWEEN'] == $filterOperator['operator']
                    && (
                        ! is_array($filterOperator['value'])
                        || count($filterOperator['value']) !== 2
                        || ! array_is_list($filterOperator['value'])
                    )
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter "%s" with operator "%s" expects a array of exactly two values.',
                            $filterKey,
                            $filterOperator['operator']
                        )
                    );
                }

                if (($this->fieldFiltersAndOperators[$filterKey]['type'] ?? null) === 'datetime') {
                    foreach ((array) $filterOperator['value'] as $dateValue) {
                        $this->assertValidDateTime($filterKey, $dateValue);
                    }
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

        if (
            in_array($value['operator'], $this->comparisonOperators(), true)
            || $this->operators['BETWEEN'] == $value['operator']
        ) {
            $scopeQueryBuilder = $this->applyComparisonFilter($scopeQueryBuilder, $requestedColumn, $value);
        }

        // Return the updated query builder instance.
        return $scopeQueryBuilder;
    }

    /**
     * The supported scalar comparison operators.
     *
     * @return array<int, string>
     */
    protected function comparisonOperators(): array
    {
        return [
            $this->operators['GREATER_THAN'],
            $this->operators['GREATER_THAN_OR_EQUAL'],
            $this->operators['LESS_THAN'],
            $this->operators['LESS_THAN_OR_EQUAL'],
        ];
    }

    /**
     * Applies a comparison (>, >=, <, <=) or BETWEEN filter. Combined with
     * AND semantics, unlike the legacy operators above: a delta filter such
     * as `updated_at > X` must narrow the result set, never widen it.
     *
     * @param  Builder  $scopeQueryBuilder
     * @return Builder
     */
    protected function applyComparisonFilter($scopeQueryBuilder, string $column, array $value)
    {
        if ($this->operators['BETWEEN'] == $value['operator']) {
            $scopeQueryBuilder->whereBetween($column, array_values($value['value']));

            return $scopeQueryBuilder;
        }

        $scopeQueryBuilder->where($column, $value['operator'], $value['value']);

        return $scopeQueryBuilder;
    }

    /**
     * @throws UnprocessableEntityHttpException When the value is not a parseable date string.
     */
    protected function assertValidDateTime(string $filterKey, mixed $value): void
    {
        if (is_string($value) && trim($value) !== '') {
            try {
                Carbon::parse($value);

                return;
            } catch (\Throwable) {
            }
        }

        throw new UnprocessableEntityHttpException(
            sprintf('Filter "%s" expects a valid date value, e.g. "2026-01-31 00:00:00".', $filterKey)
        );
    }

    /**
     * Process requested sorting.
     *
     * @return Builder
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
     *
     * Cursor mode uses simplePaginate: no COUNT(*) query, constant cost at
     * any depth — offset pagination degrades linearly on large catalogs.
     */
    public function processRequestedPagination($requestedPagination)
    {
        return $this->queryBuilder->paginate(
            $this->resolvePerPage($requestedPagination['limit'] ?? null),
            ['*'],
            $this->useCursorPagination ? 'simplePaginate' : 'paginate'
        );
    }

    /**
     * The column keyset pagination cursors on; must match the enforced sort order.
     */
    protected function cursorColumn(): string
    {
        return $this->sortColumn ?: $this->primaryColumn;
    }

    /**
     * Enables cursor mode when the request asks for search_after pagination,
     * validating the cursor value.
     *
     * @throws UnprocessableEntityHttpException When pagination_type or search_after is invalid.
     */
    protected function resolveCursorPagination(array $requestedParams): void
    {
        $paginationType = $requestedParams['pagination_type'] ?? null;
        $searchAfter = $requestedParams['search_after'] ?? null;

        if ($paginationType !== null && ! in_array($paginationType, ['page', self::PAGINATION_SEARCH_AFTER], true)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Pagination type "%s" is not supported. Use "page" or "%s".', $paginationType, self::PAGINATION_SEARCH_AFTER)
            );
        }

        $this->useCursorPagination = $paginationType === self::PAGINATION_SEARCH_AFTER || $searchAfter !== null;

        if (! $this->useCursorPagination || $searchAfter === null || $searchAfter === '') {
            return;
        }

        if (! is_numeric($searchAfter) || (int) $searchAfter < 0) {
            throw new UnprocessableEntityHttpException('The search_after cursor must be a positive integer.');
        }

        $this->searchAfter = (int) $searchAfter;
    }

    /**
     * Resolves a safe per-page value, clamped between 1 and the allowed maximum.
     */
    protected function resolvePerPage(mixed $limit): int
    {
        if (! is_numeric($limit)) {
            return $this->itemsPerPage;
        }

        return (int) max(1, min((int) $limit, $this->maxItemsPerPage));
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
        $requestedParams = request()->only(['filters', 'sort', 'limit', 'page', 'pagination_type', 'search_after']);

        $this->resolveCursorPagination($requestedParams);

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
     * @param  array  $data  The channel data from the database.
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
        if ($this->useCursorPagination) {
            return $this->responseFormatCursorData();
        }

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
     * Cursor-mode response: no total/last_page (they would need the COUNT(*)
     * cursor mode exists to avoid); `search_after` carries the next cursor.
     */
    protected function responseFormatCursorData(): array
    {
        $items = $this->paginator->items();
        $lastItem = $items === [] ? null : end($items);

        $lastId = null;

        if ($lastItem !== null) {
            $lastId = is_array($lastItem)
                ? ($lastItem[$this->primaryColumn] ?? null)
                : ($lastItem->{$this->primaryColumn} ?? null);
        }

        $hasMore = $this->paginator->hasMorePages() && $lastId !== null;

        return [
            'data'         => $this->formatData(),
            'search_after' => $hasMore ? (int) $lastId : null,
            'links'        => [
                'next' => $hasMore
                    ? request()->fullUrlWithQuery([
                        'pagination_type' => self::PAGINATION_SEARCH_AFTER,
                        'search_after'    => (int) $lastId,
                    ])
                    : null,
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
        $structureCache = app(StructureCache::class);

        if (! $this->structureCacheGroup || ! $structureCache->enabled()) {
            $this->prepare();

            return response()->json($this->responseFormatData());
        }

        $payload = $structureCache->remember(
            $this->structureCacheGroup,
            sha1(json_encode(request()->only(['filters', 'sort', 'limit', 'page', 'pagination_type', 'search_after']))),
            function (): array {
                $this->prepare();

                return $this->responseFormatData();
            }
        );

        return response()->json($payload);
    }
}
