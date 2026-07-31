<?php

namespace Webkul\Product\Filter\Database\Property;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Completeness score filter for a database query
 */
class CompletenessFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'completeness';

    const COLUMN = 'avg_completeness_score';

    const COMPARISONS = [
        FilterOperators::LESS_THAN->value              => '<',
        FilterOperators::LESS_THAN_OR_EQUAL->value     => '<=',
        FilterOperators::GREATER_THAN->value           => '>',
        FilterOperators::GREATER_THAN_OR_EQUAL->value  => '>=',
        FilterOperators::EQUAL->value                  => '=',
    ];

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [
            FilterOperators::EQUAL,
            FilterOperators::LESS_THAN,
            FilterOperators::LESS_THAN_OR_EQUAL,
            FilterOperators::GREATER_THAN,
            FilterOperators::GREATER_THAN_OR_EQUAL,
            FilterOperators::RANGE,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
    ) {
        $this->allowedOperators = $allowedOperators;
        $this->supportedProperties = $supportedProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []): static
    {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        if (! in_array($property, $this->supportedProperties)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported property name for completeness filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        /** raw statements bypass the builder, so the prefix is applied here */
        $column = sprintf('%s%s.%s', DB::getTablePrefix(), $this->getSearchTablePath($options), self::COLUMN);

        if ($comparison = self::COMPARISONS[$operator->value] ?? null) {
            $this->queryBuilder->whereRaw("$column $comparison ?", [(float) $this->scalarValue($value)]);

            return $this;
        }

        match ($operator) {
            FilterOperators::RANGE => $this->queryBuilder->whereRaw("$column BETWEEN ? AND ?", [
                (float) ($value[0] ?? 0),
                (float) ($value[1] ?? 0),
            ]),
            FilterOperators::IS_EMPTY     => $this->queryBuilder->whereRaw("$column IS NULL"),
            FilterOperators::IS_NOT_EMPTY => $this->queryBuilder->whereRaw("$column IS NOT NULL"),
            default                       => $this,
        };

        return $this;
    }
}
