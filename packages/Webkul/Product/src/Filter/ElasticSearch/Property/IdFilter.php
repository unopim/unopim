<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Id filter for an Elasticsearch query
 */
class IdFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'product_id';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $supportedOperators = [Operators::IN_LIST]
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
                    'Unsupported property name for id filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $clause = [
                    'terms' => [
                        'id' => $value,
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
