<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Id filter for an Database query
 */
class IdFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'product_id';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [FilterOperators::IN]
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
                    'Unsupported property name for id filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        if ($operator === FilterOperators::IN) {
            $this->queryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), 'id'), $value);
        }

        return $this;
    }
}
