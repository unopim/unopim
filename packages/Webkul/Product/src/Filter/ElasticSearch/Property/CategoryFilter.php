<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\QueryString;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Category filter for an Elasticsearch query.
 *
 * Variants are indexed with resolved values, so the indexed array already covers inheritance.
 */
class CategoryFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'categories';

    const FIELD = 'values.categories';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::NOT_IN,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
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
                    'Unsupported property name for category filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        $terms = ['terms' => [self::FIELD => QueryString::escapeArrayValue(array_values((array) $value))]];

        match ($operator) {
            FilterOperators::IN           => $this->queryBuilder::where($terms),
            FilterOperators::NOT_IN       => $this->queryBuilder::whereNot($terms),
            FilterOperators::IS_EMPTY     => $this->queryBuilder::whereNot(['exists' => ['field' => self::FIELD]]),
            FilterOperators::IS_NOT_EMPTY => $this->queryBuilder::where(['exists' => ['field' => self::FIELD]]),
            default                       => $this,
        };

        return $this;
    }
}
