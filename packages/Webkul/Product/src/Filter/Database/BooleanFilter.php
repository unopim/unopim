<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Contracts\Filter as FilterContract;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Boolean filter for an Database query
 */
class BooleanFilter extends AbstractDatabaseAttributeFilter implements FilterContract
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [Attribute::BOOLEAN_FIELD_TYPE],
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

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereRaw(
                    sprintf(
                        "JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?",
                        $this->getSearchTablePath($options),
                        $attributePath
                    ),
                    [is_array($value) ? implode('|', array_map(function ($val) {
                        return ($val == '1') ? 'true' : 'false';
                    }, $value)) : (($value == '1') ? 'true' : 'false')]
                );

                break;

            case FilterOperators::EQUAL:
                $this->queryBuilder->whereRaw(
                    sprintf(
                        "JSON_UNQUOTE(JSON_EXTRACT(%s, '%s')) REGEXP ?",
                        $this->getSearchTablePath($options),
                        $attributePath
                    ),
                    [is_array($value) ? implode('|', array_map(function ($val) {
                        return ($val == '1') ? 'true' : 'false';
                    }, $value)) : (($value == '1') ? 'true' : 'false')]
                );

                break;
        }

        return $this;
    }
}
