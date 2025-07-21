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
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[0], AttributeTypes::ATTRIBUTE_TYPES[1]],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::CONTAINS, FilterOperators::WILDCARD]
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
                        $attributePath => $value,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::CONTAINS:
                $escapedQuery = preg_replace('/([+\-&|!(){}[\]^"~*?:\\/])/', '\\\$1', $value);
                $clause = [
                    'query_string' => [
                        'default_field'    => $attributePath,
                            'query' => implode(' OR ', array_map(fn($term) => "*{$term}*", $escapedQuery)),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::WILDCARD:
                $escapedValue = QueryString::escapeValue(current((array) $value));
                $clause = [
                    'wildcard' => [
                        $attributePath.'.keyword' => '*'.$escapedValue.'*',
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }

    protected function getScopedAttributePath($attribute, ?string $locale = null, ?string $channel = null)
    {
        return sprintf('values.%s.%s', $attribute->getScope($locale, $channel), $attribute->code.'-'.$attribute->type. '.keyword');
    }
}
