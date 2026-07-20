<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Boolean filter for an Elasticsearch query
 */
class BooleanFilter extends AbstractElasticSearchAttributeFilter
{
    public function __construct(
        array $supportedAttributeTypes = [Attribute::BOOLEAN_FIELD_TYPE],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::CONTAINS,
            FilterOperators::EQUAL,
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
        $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case FilterOperators::IN:
            case FilterOperators::EQUAL:
                $clause = [
                    'terms' => [
                        $attributePath => array_map(fn ($val): bool => $val == '1', $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::EQUAL:
                $this->queryBuilder::where([
                    'term' => [$attributePath => current((array) $value)],
                ]);

                break;
        }

        return $this;
    }
}
