<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Completeness score filter for an Elasticsearch query
 */
class CompletenessFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'completeness';

    const FIELD = 'avg_completeness_score';

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

        switch ($operator) {
            case FilterOperators::EQUAL:
                $this->queryBuilder::where([
                    'term' => [self::FIELD => (float) $this->scalarValue($value)],
                ]);
                break;

            case FilterOperators::LESS_THAN:
            case FilterOperators::LESS_THAN_OR_EQUAL:
            case FilterOperators::GREATER_THAN:
            case FilterOperators::GREATER_THAN_OR_EQUAL:
                $this->queryBuilder::where([
                    'range' => [self::FIELD => [$operator->value => (float) $this->scalarValue($value)]],
                ]);
                break;

            case FilterOperators::RANGE:
                $values = array_values((array) $value);

                $this->queryBuilder::where([
                    'range' => [
                        self::FIELD => [
                            'gte' => (float) ($values[0] ?? 0),
                            'lte' => (float) ($values[1] ?? 0),
                        ],
                    ],
                ]);
                break;

            case FilterOperators::IS_EMPTY:
                $this->queryBuilder::whereNot(['exists' => ['field' => self::FIELD]]);
                break;

            case FilterOperators::IS_NOT_EMPTY:
                $this->queryBuilder::where(['exists' => ['field' => self::FIELD]]);
                break;
        }

        return $this;
    }
}
