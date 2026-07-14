<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Price filter for an Elasticsearch query
 */
class PriceFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [Attribute::PRICE_FIELD_TYPE],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::EQUAL,
            FilterOperators::LESS_THAN,
            FilterOperators::LESS_THAN_OR_EQUAL,
            FilterOperators::GREATER_THAN,
            FilterOperators::GREATER_THAN_OR_EQUAL,
            FilterOperators::RANGE,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
    ) {
        $this->supportedAttributeTypes = $supportedAttributeTypes;
        $this->allowedOperators = $allowedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttributeFilter(
        $attribute,
        $operator,
        $value,
        $locale = null,
        $channel = null,
        $options = []
    ) {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }
        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        $field = sprintf('%s.%s', $attributePath, $value[0]);

        switch ($operator) {
            case FilterOperators::IS_EMPTY:
                $this->queryBuilder::whereNot(['exists' => ['field' => $field]]);

                return $this;

            case FilterOperators::IS_NOT_EMPTY:
                $this->queryBuilder::where(['exists' => ['field' => $field]]);

                return $this;
        }

        if (! is_numeric($value[1])) {
            return $this;
        }

        switch ($operator) {
            case FilterOperators::EQUAL:
                $this->queryBuilder::where([
                    'term' => [$field => $value[1]],
                ]);

                break;

            case FilterOperators::LESS_THAN:
            case FilterOperators::LESS_THAN_OR_EQUAL:
            case FilterOperators::GREATER_THAN:
            case FilterOperators::GREATER_THAN_OR_EQUAL:
                $this->queryBuilder::where([
                    'range' => [$field => [$operator->value => $value[1]]],
                ]);

                break;

            case FilterOperators::RANGE:
                $this->queryBuilder::where([
                    'range' => [$field => ['gte' => $value[1], 'lte' => $value[2] ?? $value[1]]],
                ]);

                break;
        }

        return $this;
    }
}
