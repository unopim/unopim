<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Filter\Operators;
use Webkul\ElasticSearch\QueryString;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Sku filter for an Elasticsearch query
 */
class SkuFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'sku';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $supportedOperators = [Operators::IN_LIST, Operators::CONTAINS]
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
                    'Unsupported property name for sku filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $this->searchQueryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), $property), $value);

                break;
            case Operators::CONTAINS:
                $this->searchQueryBuilder->where(function ($query) use ($options, $property, $value) {
                    foreach ($value as $val) {
                        $escapedValue = QueryString::escapeValue($val);
                        $query->orWhere(sprintf('%s.%s', $this->getSearchTablePath($options), $property), 'LIKE', "%{$escapedValue}%");
                    }
                });

                break;
        }

        return $this;
    }
}
