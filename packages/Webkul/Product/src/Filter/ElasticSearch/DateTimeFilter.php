<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;

/**
 * DateTime filter for an Elasticsearch query
 */
class DateTimeFilter extends AbstractElasticSearchAttributeFilter implements FilterInterface
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[6]],
        array $supportedOperators = [Operators::IN_LIST, Operators::BETWEEN]
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

        $attributeCode = $attribute->code;

        $attributePath = $this->getAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case Operators::IN_LIST:
                $clause = [
                    'terms' => [
                        $attributePath => array_map(function ($data) use ($attributeCode) {
                            return $this->getFormattedDateTime($attributeCode, $data);
                        }, $value),
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::BETWEEN:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        "$attributePath.keyword" => [
                            'gte' => $this->getFormattedDateTime($attributeCode, $values[0]),
                            'lte' => $this->getFormattedDateTime($attributeCode, $values[1]),
                        ],
                    ],
                ];

                $this->searchQueryBuilder::addMust($clause);
                break;
        }

        return $this;
    }
}
