<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
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

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereRaw(
                    $searchPath.' '.$grammar->getRegexOperator().' ?',
                    is_array($value) ? implode('|', $value) : $value
                );

                break;

            case FilterOperators::CONTAINS:
                $this->queryBuilder->where(function ($query) use ($searchPath, $value) {
                    foreach ($value as $val) {
                        $escapedValue = strtolower(QueryString::escapeValue($val));

                        $query->orWhereRaw(
                            "LOWER($searchPath) LIKE ?",
                            "%$escapedValue%"
                        );
                    }
                });

                break;
        }

        return $this;
    }
}
