<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
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
                    'Unsupported property name for sku filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $property => QueryString::escapeArrayValue($value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::CONTAINS:
                $clause = [
                    'query_string' => [
                        'default_field' => $property,
                        'query'            => '*'.implode('* OR *', $value).'*',
                    ],
                ];
                
                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
