<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\QueryString;

/**
 * Text filter for an Elasticsearch query
 */
class TextFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[0], AttributeTypes::ATTRIBUTE_TYPES[4], AttributeTypes::ATTRIBUTE_TYPES[5]],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::CONTAINS]
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

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $attributePath => QueryString::escapeArrayValue($value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::CONTAINS:
                $escapedValue = QueryString::escapeValue(current((array) $value));
                $clause = [
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => '*'.$escapedValue.'*',
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::WILDCARD:
                $escapedValue = QueryString::escapeValue(current((array) $value));
                $clause = [
                    'wildcard' => [
                        $attributePath => $escapedValue,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
