<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Date filter for an Database query
 */
class DateFilter extends AbstractDatabaseAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[6], AttributeTypes::ATTRIBUTE_TYPES[7]],
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

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?", $this->getSearchTablePath($options), $attributePath),
                    is_array($value) ? implode('|', $value) : $value
                );

                break;

            case FilterOperators::RANGE:
                $this->queryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) BETWEEN ? AND ?", $this->getSearchTablePath($options), $attributePath),
                    [
                        ($value[0] ?? '').' 00:00:01',
                        ($value[1] ?? '').' 23:59:59',
                    ]
                );

                break;
        }

        return $this;
    }
}
