<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\QueryString;

/**
 * Sku or name filter for an Elasticsearch query
 */
class SkuOrUniversalFilter extends AbstractElasticSearchAttributeFilter
{
    public function __construct(
        protected AttributeService $attributeService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function applyUnfilteredFilter(
        $fields,
        $operator,
        $value,
        $options = []
    ) {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        $clauses = [];

        foreach ($fields as $attribute) {
            $attribute = $this->attributeService->findAttributeByCode($attribute);
            if (! $attribute) {
                continue;
            }

            $locale = $attribute->value_per_locale ? $options['locale'] : null;
            $channel = $attribute->value_per_channel ? $options['channel'] : null;

            $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);
            $escapedValue = QueryString::escapeValue(current((array) $value));

            $attributeType = $attribute->type;

            if ($attributeType === 'text' || $attributeType === 'textarea') {
                /**
                 * For text fields, use match_phrase_prefix on the text field
                 * instead of wildcard on .keyword to avoid exceeding
                 * maxClauseCount on high-cardinality indexes.
                 */
                $clauses[] = [
                    'match_phrase_prefix' => [
                        $attributePath => [
                            'query'          => $escapedValue,
                            'max_expansions' => 1000,
                        ],
                    ],
                ];

                continue;
            }

            /**
             * For keyword fields (e.g., sku), use wildcard with
             * rewrite: 'top_terms_1024' to cap internal clause expansion
             * and avoid too_many_clauses error on large indexes.
             */
            $clauses[] = [
                'wildcard' => [
                    $attributePath => [
                        'value'   => '*'.$escapedValue.'*',
                        'rewrite' => 'top_terms_1024',
                    ],
                ],
            ];
        }

        $this->queryBuilder::where([
            'bool' => [
                'should'               => $clauses,
                'minimum_should_match' => 1,
            ],
        ]);

        return $this;
    }
}
