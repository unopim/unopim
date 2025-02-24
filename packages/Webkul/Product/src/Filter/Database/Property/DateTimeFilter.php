<?php

namespace Webkul\Product\Filter\Database\Property;

use Webkul\ElasticSearch\Filter\Operators;
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
                $this->searchQueryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), $property), $value);

                break;

            case Operators::BETWEEN:
                $this->searchQueryBuilder->whereBetween(sprintf('%s.%s', $this->getSearchTablePath($options), $property), [
                    ($value[0] ?? '').' 00:00:01',
                    ($value[1] ?? '').' 23:59:59',
                ]);

                break;
        }

        return $this;
    }
}
