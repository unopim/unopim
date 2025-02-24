<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
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
        array $supportedOperators = [Operators::IN_LIST, Operators::BETWEEN]
    ) {
        $this->supportedOperators = $supportedOperators;
        $this->supportedProperties = $supportedProperties;

    }

    /**
     * {@inheritdoc}
     */
    public function addPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = [])
    {
        if ($this->searchQueryBuilder === null) {
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
            case Operators::IN_LIST:
                $clause = [
                    'terms' => [
                        $property => array_map(function ($data) use ($property) {
                            return $this->getFormattedDateTime($property, $data);
                        }, $value),
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::BETWEEN:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $property => [
                            'gte' => $this->getFormattedDateTime($property, $values[0]),
                            'lte' => $this->getFormattedDateTime($property, $values[1]),
                        ],
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
