<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;

/**
 * Boolean filter for an Database query
 */
class BooleanFilter extends AbstractDatabaseAttributeFilter implements FilterInterface
{
    /**
     * @param  array  $supportedFields
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[3]],
        array $supportedOperators = [Operators::IN_LIST, Operators::EQUALS]
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
                $this->searchQueryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?", $this->getSearchTablePath($options), $attributePath),
                    is_array($value) ? implode('|', $value) : $value
                );

                break;
            case Operators::EQUALS:
                $this->searchQueryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?", $this->getSearchTablePath($options), $attributePath),
                    is_array($value) ? implode('|', $value) : $value
                );

                break;
        }

        return $this;
    }
}
