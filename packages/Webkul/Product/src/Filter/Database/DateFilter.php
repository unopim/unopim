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
    public function __construct(
        array $supportedAttributeTypes = [Attribute::DATE_FIELD_TYPE, Attribute::DATETIME_FIELD_TYPE],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::RANGE,
            FilterOperators::LESS_THAN,
            FilterOperators::GREATER_THAN,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
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
        ?string $locale = null,
        ?string $channel = null,
        array $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        match ($operator) {
            FilterOperators::IN => $this->queryBuilder->whereRaw(
                $searchPath.' '.$grammar->getRegexOperator().' ?',
                is_array($value) ? implode('|', $value) : $value
            ),
            FilterOperators::RANGE => $this->queryBuilder->whereRaw(
                $searchPath.' BETWEEN ? AND ?',
                [
                    ($value[0] ?? '').' 00:00:01',
                    ($value[1] ?? '').' 23:59:59',
                ]
            ),
            FilterOperators::LESS_THAN => $this->queryBuilder->whereRaw(
                $searchPath.' < ?',
                [$this->scalarValue($value).' 00:00:01']
            ),
            FilterOperators::GREATER_THAN => $this->queryBuilder->whereRaw(
                $searchPath.' > ?',
                [$this->scalarValue($value).' 23:59:59']
            ),
            FilterOperators::IS_EMPTY     => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') = ''"),
            FilterOperators::IS_NOT_EMPTY => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') != ''"),
            default                       => $this,
        };

        return $this;
    }
}
