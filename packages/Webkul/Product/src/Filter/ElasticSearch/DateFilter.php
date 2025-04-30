<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Date filter for an Elasticsearch query
 */
class DateFilter extends AbstractElasticSearchAttributeFilter
{
    protected $dateFormat = 'Y-m-d';

    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[7]],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::RANGE]
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

        $attributeCode = $attribute->code;

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $attributePath => array_map(function ($data) use ($attributeCode) {
                            return $this->getFormattedDateTime($attributeCode, $data);
                        }, $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::RANGE:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $attributePath => [
                            'gte' => $this->getFormattedDateTime($attributeCode, $values[0]),
                            'lte' => $this->getFormattedDateTime($attributeCode, $values[1]),
                        ],
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
