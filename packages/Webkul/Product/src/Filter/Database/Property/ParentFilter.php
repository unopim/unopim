<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Parent filter for an Elasticsearch query
 */
class ParentFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'parent';

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
                    'Unsupported property name for parent filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $this->searchQueryBuilder->whereIn(
                    sprintf('%s.%s', $this->getSearchTablePath($options), 'parent_id'),
                    $this->getParentIdsBySkus($value, $options)
                );
                break;
        }

        return $this;
    }
}
