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
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[6], AttributeTypes::ATTRIBUTE_TYPES[7]],
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

        $attributePath = $this->getAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case Operators::IN_LIST:
                $this->searchQueryBuilder->whereRaw(
                    sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?", $this->getSearchTablePath($options), $attributePath),
                    is_array($value) ? implode('|', $value) : $value
                );

                break;

            case Operators::BETWEEN:
                $this->searchQueryBuilder->whereRaw(
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
