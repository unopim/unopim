<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Id filter for an Elasticsearch query
 */
class IdFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'product_id';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::NOT_EQUAL, FilterOperators::NOT_IN]
    ) {
        $this->allowedOperators = $allowedOperators;
        $this->supportedProperties = $supportedProperties;

    }

    /**
     * {@inheritdoc}
     */
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = [])
    {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        if (! in_array($property, $this->supportedProperties)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported property name for id filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        'id' => $value,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::NOT_IN:
                $clause = [
                    'terms' => [
                        'id' => $value,
                    ],
                ];

                $this->queryBuilder::whereNot($clause);
                break;

            case FilterOperators::NOT_EQUAL:
                $clause = [
                    'term' => [
                        'id' => $value,
                    ],
                ];

                $this->queryBuilder::whereNot($clause);
                break;
        }

        return $this;
    }
}
