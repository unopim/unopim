<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\ElasticSearch\QueryString;

/**
 * Text filter for an Elasticsearch query
 */
class TextFilter extends AbstractElasticSearchAttributeFilter implements FilterInterface
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[0], AttributeTypes::ATTRIBUTE_TYPES[4], AttributeTypes::ATTRIBUTE_TYPES[5]],
        array $supportedOperators = [Operators::IN_LIST, Operators::CONTAINS]
    ) {
        $this->supportedAttributeTypes = $supportedAttributeTypes;
        $this->supportedOperators = $supportedOperators;
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
        if ($this->searchQueryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        $attributePath = $this->getAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case Operators::IN_LIST:
                $clause = [
                    'terms' => [
                        $attributePath => QueryString::escapeArrayValue($value),
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::CONTAINS:
                $escapedValue = QueryString::escapeValue(current((array) $value));
                $clause = [
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => '*'.$escapedValue.'*',
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
