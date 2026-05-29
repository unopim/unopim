<?php

namespace Webkul\Product\Filter\Database\Property;

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
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []): static
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

        match ($operator) {
            FilterOperators::IN       => $this->queryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), $property), $value),
            FilterOperators::CONTAINS => $this->queryBuilder->where(function (mixed $query) use ($options, $property, $value) {
                foreach ($value as $val) {
                    $escapedValue = QueryString::escapeValue($val);
                    $query->orWhere(sprintf('%s.%s', $this->getSearchTablePath($options), $property), 'LIKE', "%{$escapedValue}%");
                }
            }),
            default => $this,
        };

        return $this;
    }
}
