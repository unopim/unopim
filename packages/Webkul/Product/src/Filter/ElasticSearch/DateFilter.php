<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Date filter for an Elasticsearch query
 */
class DateFilter extends AbstractElasticSearchAttributeFilter
{
    protected $dateFormat = 'Y-m-d';

    public function __construct(
        array $supportedAttributeTypes = [Attribute::DATE_FIELD_TYPE],
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
        $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $attributeCode = $attribute->code;

        $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $attributePath => array_map(fn (string $data): string => $this->getFormattedDateTime($attributeCode, $data), $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::RANGE:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $attributePath => [
                            'gte' => $this->getFormattedDateTime($attributeCode, $values[0]),
                            'lte' => $this->getFormattedDateTime($attributeCode, $values[1]),
                        ],
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::LESS_THAN:
            case FilterOperators::GREATER_THAN:
                $this->queryBuilder::where([
                    'range' => [$attributePath => [$operator->value => current((array) $value)]],
                ]);

                break;

            case FilterOperators::IS_EMPTY:
                $this->queryBuilder::whereNot(['exists' => ['field' => $attributePath]]);

                break;

            case FilterOperators::IS_NOT_EMPTY:
                $this->queryBuilder::where(['exists' => ['field' => $attributePath]]);

                break;
        }

        return $this;
    }
}
