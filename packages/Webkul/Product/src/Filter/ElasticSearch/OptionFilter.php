<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Option filter for an Elasticsearch query
 */
class OptionFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[4], AttributeTypes::ATTRIBUTE_TYPES[5]],
        array $allowedOperators = [FilterOperators::IN]
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
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => $attribute->type == Attribute::MULTISELECT_FIELD_TYPE ? '*'.implode('* OR *', $value).'*' : implode(' OR ', $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
