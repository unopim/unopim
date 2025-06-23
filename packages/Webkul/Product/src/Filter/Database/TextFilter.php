<?php

namespace Webkul\Product\Filter\Database;

use Webkul\Attribute\Models\Attribute;
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
        array $supportedAttributeTypes = [Attribute::TEXT_TYPE, Attribute::TEXTAREA_TYPE, Attribute::SELECT_FIELD_TYPE, Attribute::MULTISELECT_FIELD_TYPE, Attribute::IMAGE_ATTRIBUTE_TYPE, Attribute::FILE_ATTRIBUTE_TYPE, Attribute::CHECKBOX_FIELD_TYPE, Attribute::GALLERY_ATTRIBUTE_TYPE],
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
