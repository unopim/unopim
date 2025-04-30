<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
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
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[2]],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::EQUAL]
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

        if (is_numeric($value[1])) {
            switch ($operator) {
                case FilterOperators::EQUAL:
                    $clause = [
                        'term' => [
                            sprintf('%s.%s', $attributePath, $value[0]) => $value[1],
                        ],
                    ];

                    $this->queryBuilder::where($clause);
                    break;
            }
        }

        return $this;
    }
}
