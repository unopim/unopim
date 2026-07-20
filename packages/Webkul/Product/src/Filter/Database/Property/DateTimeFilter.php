<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * DateTime filter for an Database query
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
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []): static
    {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        if (! in_array($property, $this->supportedProperties)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported property name for datetime filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        match ($operator) {
            FilterOperators::IN    => $this->queryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), $property), $value),
            FilterOperators::RANGE => $this->queryBuilder->whereBetween(sprintf('%s.%s', $this->getSearchTablePath($options), $property), [
                ($value[0] ?? '').' 00:00:01',
                ($value[1] ?? '').' 23:59:59',
            ]),
            default => $this,
        };

        return $this;
    }
}
