<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * DateTime filter for an Elasticsearch query
 */
class DateTimeFilter extends AbstractPropertyFilter
{
    const CREATED_AT_PROPERTY = 'created_at';

    const UPDATED_AT_PROPERTY = 'updated_at';

    public function __construct(
        array $supportedProperties = [self::CREATED_AT_PROPERTY, self::UPDATED_AT_PROPERTY],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::RANGE]
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
                    'Unsupported property name for datetime filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $property => array_map(function ($data) use ($property) {
                            return $this->getFormattedDateTime($property, $data);
                        }, $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::RANGE:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $property => [
                            'gte' => $this->getFormattedDateTime($property, $values[0]),
                            'lte' => $this->getFormattedDateTime($property, $values[1]),
                        ],
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
