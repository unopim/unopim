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
        ?string $locale = null,
        ?string $channel = null,
        array $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        $grammar = DB::rawQueryGrammar();

        $searchPath = $grammar->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

        $searchPath .= ' '.$grammar->getRegexOperator().' ?';

        match ($operator) {
            FilterOperators::IN => $this->queryBuilder->whereRaw(
                $searchPath,
                $this->formatBooleanValue($value)
            ),
            FilterOperators::EQUAL => $this->queryBuilder->whereRaw(
                $searchPath,
                $this->formatBooleanValue($value),
            ),
            default => $this,
        };

        return $this;
    }

    private function formatBooleanValue(mixed $value): array
    {
        return [
            is_array($value)
                ? implode('|', array_map(fn ($val): string => ($val == '1' ? 'true' : 'false'), $value))
                : ($value == '1' ? 'true' : 'false'),
        ];
    }
}
