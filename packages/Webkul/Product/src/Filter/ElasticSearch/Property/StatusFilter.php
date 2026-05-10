<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Status filter for an Elasticsearch query
 */
class StatusFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'status';

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
                // The ES mapping for `status` is a boolean field (see
                // ProductIndexer). ES8's strict parser rejects the raw "1"/"0"
                // strings the DataGrid forwards from filter option values, so
                // coerce each candidate to a real boolean before emitting the
                // terms clause.
                $values = array_values(array_unique(array_map(
                    fn ($item) => filter_var($item, FILTER_VALIDATE_BOOLEAN),
                    (array) $value
                )));

                $clause = [
                    'terms' => [
                        $property => $values,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
