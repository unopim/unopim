<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Price filter for an Database query
 */
class PriceFilter extends AbstractDatabaseAttributeFilter
{
    public function __construct(
        array $supportedAttributeTypes = [Attribute::PRICE_FIELD_TYPE],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::EQUAL,
            FilterOperators::LESS_THAN,
            FilterOperators::LESS_THAN_OR_EQUAL,
            FilterOperators::GREATER_THAN,
            FilterOperators::GREATER_THAN_OR_EQUAL,
            FilterOperators::RANGE,
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

        // Add currency to the attribute path to access the filtered price value like USD, EUR
        $attributePath[] = $value[0];

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        match ($operator) {
            FilterOperators::IN => $this->queryBuilder->whereRaw(
                $searchPath.' '.$grammar->getRegexOperator().' ?',
                $value[1]
            ),
            FilterOperators::EQUAL => $this->queryBuilder->whereRaw(
                "CAST($searchPath AS DECIMAL(8,2)) = ?",
                $value[1]
            ),
            FilterOperators::LESS_THAN, FilterOperators::LESS_THAN_OR_EQUAL, FilterOperators::GREATER_THAN, FilterOperators::GREATER_THAN_OR_EQUAL => $this->queryBuilder->whereRaw(
                "CAST($searchPath AS DECIMAL(8,2)) ".self::COMPARISONS[$operator->value].' ?',
                $value[1]
            ),
            FilterOperators::RANGE => $this->queryBuilder->whereRaw(
                "CAST($searchPath AS DECIMAL(8,2)) BETWEEN ? AND ?",
                [$value[1], $value[2] ?? $value[1]]
            ),
            FilterOperators::IS_EMPTY     => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') = ''"),
            FilterOperators::IS_NOT_EMPTY => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') != ''"),
            default                       => $this,
        };

        return $this;
    }
}
