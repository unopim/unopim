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
    public function __construct(
        array $supportedAttributeTypes = [Attribute::TEXT_TYPE, Attribute::TEXTAREA_TYPE, Attribute::SELECT_FIELD_TYPE, Attribute::MULTISELECT_FIELD_TYPE, Attribute::IMAGE_ATTRIBUTE_TYPE, Attribute::FILE_ATTRIBUTE_TYPE, Attribute::CHECKBOX_FIELD_TYPE, Attribute::GALLERY_ATTRIBUTE_TYPE],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::NOT_IN,
            FilterOperators::CONTAINS,
            FilterOperators::EQUAL,
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
                $this->listPattern($value)
            ),
            FilterOperators::CONTAINS => $this->queryBuilder->where(function ($query) use ($searchPath, $value): void {
                foreach ($value as $val) {
                    $escapedValue = strtolower(QueryString::escapeValue($val));

                    $query->orWhereRaw(
                        "LOWER($searchPath) LIKE ?",
                        "%$escapedValue%"
                    );
                }
            }),
            FilterOperators::NOT_IN => $this->queryBuilder->whereRaw(
                'NOT ('.$searchPath.' '.$grammar->getRegexOperator().' ?)',
                $this->listPattern($value)
            ),
            FilterOperators::EQUAL => $this->queryBuilder->whereRaw(
                "LOWER($searchPath) = ?",
                strtolower((string) $this->scalarValue($value))
            ),
            FilterOperators::IS_EMPTY     => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') = ''"),
            FilterOperators::IS_NOT_EMPTY => $this->queryBuilder->whereRaw("COALESCE($searchPath, '') != ''"),
            default                       => $this,
        };

        return $this;
    }

    /**
     * Build a pattern matching any of the values as a whole entry of a comma-separated value.
     *
     * Values are quoted so they never act as regex syntax, and anchored so `red` does not
     * match `red_dark`, the same exact-term semantics as the Elasticsearch filters.
     */
    protected function listPattern(mixed $value): string
    {
        $values = array_map(fn (mixed $item): string => preg_quote((string) $item), (array) $value);

        return '(^|,)('.implode('|', $values).')(,|$)';
    }
}
