<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
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
        array $supportedAttributeTypes = [Attribute::DATE_FIELD_TYPE, Attribute::DATETIME_FIELD_TYPE],
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

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereRaw(
                    $searchPath.' '.$grammar->getRegexOperator().' ?',
                    is_array($value) ? implode('|', $value) : $value
                );

                break;

            case FilterOperators::RANGE:
                $this->queryBuilder->whereRaw(
                    $searchPath.' BETWEEN ? AND ?',
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
