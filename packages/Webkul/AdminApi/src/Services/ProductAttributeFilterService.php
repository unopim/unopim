<?php

namespace Webkul\AdminApi\Services;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webkul\Admin\Filters\ProductFilterOperators;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\ElasticSearch\Cursor\MatchingIdCursor;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Builders\DatabaseProductQueryBuilder;
use Webkul\Product\Builders\ElasticProductQueryBuilder;
use Webkul\Product\Filter\FilterManager;

/**
 * Filters the product API listing by attribute values through the product grid's filter engine.
 */
class ProductAttributeFilterService
{
    /**
     * API operators mapped to the filter engine operators they stand for.
     */
    public const OPERATORS = [
        '='         => FilterOperators::EQUAL,
        'IN'        => FilterOperators::IN,
        'NOT IN'    => FilterOperators::NOT_IN,
        'CONTAINS'  => FilterOperators::CONTAINS,
        '<'         => FilterOperators::LESS_THAN,
        '<='        => FilterOperators::LESS_THAN_OR_EQUAL,
        '>'         => FilterOperators::GREATER_THAN,
        '>='        => FilterOperators::GREATER_THAN_OR_EQUAL,
        'BETWEEN'   => FilterOperators::RANGE,
        'EMPTY'     => FilterOperators::IS_EMPTY,
        'NOT EMPTY' => FilterOperators::IS_NOT_EMPTY,
    ];

    /**
     * Above this many Elasticsearch matches the filters run on the database instead,
     * which keeps the id list handed to SQL bounded.
     */
    protected int $maxElasticsearchMatches = 10000;

    protected int $elasticsearchBatchSize = 2000;

    /**
     * @var array<string, FilterManager>
     */
    protected array $filterManagers = [];

    public function __construct(
        protected AttributeService $attributeService,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
    ) {}

    /**
     * Validate attribute filter criteria and shape them for the filter engine.
     *
     * @param  array<array-key, array<int, array<string, mixed>>>  $filters
     * @return list<array{attribute: Attribute, operator: FilterOperators, value: array<int, mixed>, context: array{locale: ?string, channel: ?string}}>
     *
     * @throws UnprocessableEntityHttpException
     */
    public function resolve(array $filters): array
    {
        $resolved = [];

        foreach ($filters as $code => $criteria) {
            $code = (string) $code;

            $attribute = $this->attributeService->findAttributeByCode($code);

            foreach ($criteria as $criterion) {
                $resolved[] = $this->resolveCriterion($code, $attribute, $criterion);
            }
        }

        return $resolved;
    }

    /**
     * The API operators an attribute can be filtered with: the grid's operators for its
     * type that every engine able to serve the request supports.
     *
     * @return list<string>
     */
    public function operatorsFor(Attribute $attribute): array
    {
        $engineFilters = [$this->filterManager(false)->getAttributeFilter($attribute, null)];

        if (config('elasticsearch.enabled')) {
            $engineFilters[] = $this->filterManager(true)->getAttributeFilter($attribute, null);
        }

        $gridOperators = array_column(ProductFilterOperators::forType($attribute->type), 'operator');

        $operators = [];

        foreach (self::OPERATORS as $apiOperator => $operator) {
            if (! in_array($operator, $gridOperators, true)) {
                continue;
            }

            foreach ($engineFilters as $filter) {
                if (! $filter || ! $filter->isOperatorAllowed($operator)) {
                    continue 2;
                }
            }

            $operators[] = $apiOperator;
        }

        return $operators;
    }

    /**
     * Constrain the product query to the products matching every resolved filter.
     *
     * Elasticsearch, when enabled, narrows the query to the matching ids; the database
     * engine takes over when the cluster is unreachable or the match set is too broad.
     *
     * @param  list<array{attribute: Attribute, operator: FilterOperators, value: array<int, mixed>, context: array{locale: ?string, channel: ?string}}>  $filters
     */
    public function apply(EloquentBuilder $query, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        if (config('elasticsearch.enabled')) {
            $ids = $this->matchingIdsFromElasticsearch($filters);

            if ($ids !== null) {
                $query->whereIntegerInRaw('products.id', $ids);

                return;
            }
        }

        $this->applyOnDatabase($query->getQuery(), $filters);
    }

    /**
     * @param  list<array{attribute: Attribute, operator: FilterOperators, value: array<int, mixed>, context: array{locale: ?string, channel: ?string}}>  $filters
     * @return list<int>|null Null when the database has to evaluate the filters.
     */
    protected function matchingIdsFromElasticsearch(array $filters): ?array
    {
        try {
            $builder = app()->make(ElasticProductQueryBuilder::class, [
                'filterManager' => $this->filterManager(true),
            ]);

            foreach ($filters as $filter) {
                $builder->applyFilter($filter['attribute']->code, $filter['operator'], $filter['value'], $filter['context']);
            }

            $ids = MatchingIdCursor::lazy(
                strtolower(config('elasticsearch.prefix').'_products'),
                $builder->build()['query'] ?? [],
                $this->elasticsearchBatchSize,
            )->take($this->maxElasticsearchMatches + 1)->values()->all();
        } catch (\Exception $e) {
            Log::error('Elasticsearch unavailable, falling back to database query: '.$e->getMessage());

            return null;
        }

        return count($ids) > $this->maxElasticsearchMatches ? null : $ids;
    }

    /**
     * @param  list<array{attribute: Attribute, operator: FilterOperators, value: array<int, mixed>, context: array{locale: ?string, channel: ?string}}>  $filters
     */
    protected function applyOnDatabase(Builder $query, array $filters): void
    {
        $builder = app()->make(DatabaseProductQueryBuilder::class, [
            'filterManager' => $this->filterManager(false),
        ]);

        $builder->setQueryManager($query);

        foreach ($filters as $filter) {
            $builder->applyFilter($filter['attribute']->code, $filter['operator'], $filter['value'], $filter['context']);
        }
    }

    /**
     * @param  array<array-key, mixed>  $criterion
     * @return array{attribute: Attribute, operator: FilterOperators, value: array<int, mixed>, context: array{locale: ?string, channel: ?string}}
     */
    protected function resolveCriterion(string $code, ?Attribute $attribute, array $criterion): array
    {
        $apiOperator = (string) $criterion['operator'];

        if (! $attribute || ! in_array($apiOperator, $this->operatorsFor($attribute), true)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter on property "%s" is not supported or does not support operator "%s".', $code, $apiOperator)
            );
        }

        $operator = self::OPERATORS[$apiOperator];

        return [
            'attribute' => $attribute,
            'operator'  => $operator,
            'value'     => $this->resolveValue($code, $attribute, $apiOperator, $operator, $criterion),
            'context'   => [
                'locale'  => $attribute->value_per_locale ? $this->resolveLocale($code, $criterion) : null,
                'channel' => $attribute->value_per_channel ? $this->resolveChannel($code, $criterion) : null,
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $criterion
     * @return array<int, mixed>
     */
    protected function resolveValue(string $code, Attribute $attribute, string $apiOperator, FilterOperators $operator, array $criterion): array
    {
        $currency = $attribute->type === Attribute::PRICE_FIELD_TYPE
            ? $this->resolveCurrency($code, $criterion)
            : '';

        if (in_array($operator, [FilterOperators::IS_EMPTY, FilterOperators::IS_NOT_EMPTY], true)) {
            return ProductFilterOperators::engineValue($attribute->type, $operator, '', '', $currency);
        }

        if (! isset($criterion['value'])) {
            throw new UnprocessableEntityHttpException(sprintf('Value is missing for the property "%s".', $code));
        }

        $value = $criterion['value'];

        if (in_array($operator, [FilterOperators::IN, FilterOperators::NOT_IN], true)) {
            if (! is_array($value) || $value === [] || ! array_is_list($value)) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Filter "%s" with operator "%s" expects a array value.', $code, $apiOperator)
                );
            }

            $values = array_map(fn (mixed $item): string|int|float => $this->resolveScalar($code, $attribute, $apiOperator, $item), $value);

            return ProductFilterOperators::engineValue($attribute->type, $operator, $values, '', $currency);
        }

        if ($operator === FilterOperators::RANGE) {
            if (! is_array($value) || count($value) !== 2 || ! array_is_list($value)) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Filter "%s" with operator "%s" expects a array of exactly two values.', $code, $apiOperator)
                );
            }

            return ProductFilterOperators::engineValue(
                $attribute->type,
                $operator,
                $this->resolveScalar($code, $attribute, $apiOperator, $value[0]),
                $this->resolveScalar($code, $attribute, $apiOperator, $value[1]),
                $currency,
            );
        }

        return ProductFilterOperators::engineValue(
            $attribute->type,
            $operator,
            $this->resolveScalar($code, $attribute, $apiOperator, $value),
            '',
            $currency,
        );
    }

    /**
     * Validate one filter value against the attribute type and normalise it for the engine.
     *
     * @throws UnprocessableEntityHttpException
     */
    protected function resolveScalar(string $code, Attribute $attribute, string $apiOperator, mixed $value): string|int|float
    {
        if (! is_scalar($value)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter "%s" with operator "%s" does not support a array value.', $code, $apiOperator)
            );
        }

        return match ($attribute->type) {
            Attribute::BOOLEAN_FIELD_TYPE => $this->booleanValue($code, $value),
            Attribute::PRICE_FIELD_TYPE   => $this->numericValue($code, $value),
            Attribute::DATE_FIELD_TYPE,
            Attribute::DATETIME_FIELD_TYPE => $this->dateValue($code, $value),
            default                        => is_bool($value) ? (int) $value : $value,
        };
    }

    protected function booleanValue(string $code, string|int|float|bool $value): string
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolean === null) {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter "%s" expects a boolean value: true, false, 1 or 0.', $code)
            );
        }

        return $boolean ? '1' : '0';
    }

    protected function numericValue(string $code, string|int|float|bool $value): int|float
    {
        if (is_bool($value) || ! is_numeric($value)) {
            throw new UnprocessableEntityHttpException(sprintf('Filter "%s" expects a numeric value.', $code));
        }

        return $value + 0;
    }

    protected function dateValue(string $code, string|int|float|bool $value): string
    {
        $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter "%s" expects a date value in the "Y-m-d" format, e.g. "2026-01-31".', $code)
            );
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $criterion
     */
    protected function resolveLocale(string $code, array $criterion): string
    {
        $locale = $criterion['locale'] ?? null;

        if (! is_string($locale) || $locale === '') {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter on attribute "%s" requires a "locale" because the attribute is localizable.', $code)
            );
        }

        if (! $this->localeRepository->findOneByField('code', $locale)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Locale "%s" of the filter on attribute "%s" does not exist.', $locale, $code)
            );
        }

        return $locale;
    }

    /**
     * @param  array<array-key, mixed>  $criterion
     */
    protected function resolveChannel(string $code, array $criterion): string
    {
        $channel = $criterion['channel'] ?? null;

        if (! is_string($channel) || $channel === '') {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter on attribute "%s" requires a "channel" because the attribute is channel-specific.', $code)
            );
        }

        if (! $this->channelRepository->findOneByField('code', $channel)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Channel "%s" of the filter on attribute "%s" does not exist.', $channel, $code)
            );
        }

        return $channel;
    }

    /**
     * @param  array<array-key, mixed>  $criterion
     */
    protected function resolveCurrency(string $code, array $criterion): string
    {
        $currency = $criterion['currency'] ?? null;

        if (! is_string($currency) || $currency === '') {
            throw new UnprocessableEntityHttpException(
                sprintf('Filter on price attribute "%s" requires a "currency".', $code)
            );
        }

        if (! $this->currencyRepository->findOneByField('code', $currency)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Currency "%s" of the filter on attribute "%s" does not exist.', $currency, $code)
            );
        }

        return $currency;
    }

    protected function filterManager(bool $elasticsearch): FilterManager
    {
        return $this->filterManagers[$elasticsearch ? 'elasticsearch' : 'database'] ??= app()->make(FilterManager::class, [
            'elasticsearch' => $elasticsearch,
        ]);
    }
}
