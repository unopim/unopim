<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;

/**
 * Date filter for an Database query
 */
class DateFilter extends AbstractDatabaseAttributeFilter implements FilterInterface
{
    /**
     * @param  array  $supportedFields
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[6], AttributeTypes::ATTRIBUTE_TYPES[7]],
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
                        $attributePath => [
                            'gte' => $this->getFormattedDateTime($attributeCode, $values[0]),
                            'lte' => $this->getFormattedDateTime($attributeCode, $values[1]),
                        ],
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
