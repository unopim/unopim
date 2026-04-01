<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\QueryString;

/**
 * Text filter for an Elasticsearch query
 */
class TextFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [AttributeTypes::ATTRIBUTE_TYPES[0], AttributeTypes::ATTRIBUTE_TYPES[1]],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::CONTAINS, FilterOperators::WILDCARD]
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
                $clause = [
                    'terms' => [
                        $attributePath => $value,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::CONTAINS:
                /**
                 * Use match_phrase_prefix for text fields instead of query_string with leading
                 * wildcards to avoid exceeding maxClauseCount on large indexes.
                 */
                $clauses = [];

                foreach ((array) $value as $val) {
                    $clauses[] = [
                        'match_phrase_prefix' => [
                            $attributePath => [
                                'query'          => $val,
                                'max_expansions' => 1000,
                            ],
                        ],
                    ];
                }

                $clause = count($clauses) === 1 ? $clauses[0] : [
                    'bool' => [
                        'should'               => $clauses,
                        'minimum_should_match' => 1,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::WILDCARD:
                /**
                 * Cap wildcard expansion with rewrite: 'top_terms_1024' to avoid
                 * exceeding maxClauseCount on high-cardinality keyword fields.
                 */
                $escapedValue = QueryString::escapeValue(current((array) $value));
                $clause = [
                    'wildcard' => [
                        $attributePath.'.keyword' => [
                            'value'   => '*'.$escapedValue.'*',
                            'rewrite' => 'top_terms_1024',
                        ],
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
