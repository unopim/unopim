<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
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

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        $searchPath .= ' '.$grammar->getRegexOperator().' ?';

        switch ($operator) {
            case FilterOperators::IN:
                $this->queryBuilder->whereRaw(
                    $searchPath,
                    $this->formatBooleanValue($value)
                );

                break;

            case FilterOperators::EQUAL:
                $this->queryBuilder->whereRaw(
                    $searchPath,
                    $this->formatBooleanValue($value),
                );

                break;
        }

        return $this;
    }

    private function formatBooleanValue(mixed $value): array
    {
        return [
            is_array($value)
                ? implode('|', array_map(fn ($val) => ($val == '1' ? 'true' : 'false'), $value))
                : ($value == '1' ? 'true' : 'false'),
        ];
    }
}
