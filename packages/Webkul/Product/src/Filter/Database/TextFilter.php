<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\QueryString;

/**
 * Text filter for an Database query
 */
class TextFilter extends AbstractDatabaseAttributeFilter
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
                $this->queryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?", $this->getSearchTablePath($options), $attributePath),
                    is_array($value) ? implode('|', $value) : $value
                );

                break;

            case FilterOperators::CONTAINS:
                $this->queryBuilder->where(function ($query) use ($attributePath, $options, $value) {
                    foreach ($value as $val) {
                        $escapedValue = strtolower(QueryString::escapeValue($val));
                        $query->orWhereRaw(
                            sprintf("LOWER(JSON_UNQUOTE(JSON_EXTRACT(%s, '%s'))) LIKE ?", $this->getSearchTablePath($options), $attributePath),
                            "%$escapedValue%"
                        );
                    }
                });

                break;
        }

        return $this;
    }
}
