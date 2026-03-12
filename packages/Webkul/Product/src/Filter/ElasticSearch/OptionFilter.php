<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Option filter for an Elasticsearch query
 */
class OptionFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [Attribute::CHECKBOX_FIELD_TYPE, Attribute::MULTISELECT_FIELD_TYPE, Attribute::SELECT_FIELD_TYPE],
        array $allowedOperators = [FilterOperators::IN]
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
                if ($attribute->type == Attribute::SELECT_FIELD_TYPE) {
                    /**
                     * For select fields, use terms query for exact matching
                     * which is more efficient than query_string.
                     */
                    $clause = [
                        'terms' => [
                            $attributePath => $value,
                        ],
                    ];
                } else {
                    /**
                     * For multiselect/checkbox, use individual wildcard clauses
                     * with rewrite capping instead of query_string with leading
                     * wildcards to avoid exceeding maxClauseCount.
                     */
                    $clauses = [];

                    foreach ($value as $val) {
                        $clauses[] = [
                            'wildcard' => [
                                $attributePath => [
                                    'value'   => '*'.$val.'*',
                                    'rewrite' => 'top_terms_1024',
                                ],
                            ],
                        ];
                    }

                    $clause = [
                        'bool' => [
                            'should'               => $clauses,
                            'minimum_should_match' => 1,
                        ],
                    ];
                }

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
