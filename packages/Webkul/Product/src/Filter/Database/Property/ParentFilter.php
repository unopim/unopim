<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Parent filter for an Elasticsearch query
 */
class ParentFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'parent';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::CONTAINS]
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
                    'Unsupported property name for parent filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereIn(
                    sprintf('%s.%s', $this->getSearchTablePath($options), 'parent_id'),
                    $this->getParentIdsBySkus($value, $options)
                );
                break;

            case FilterOperators::CONTAINS:
                $this->queryBuilder->whereIn(
                    sprintf('%s.%s', $this->getSearchTablePath($options), 'parent_id'),
                    $this->getParentIdsBySkus($value, $options)
                );
                break;
        }

        return $this;
    }
}
