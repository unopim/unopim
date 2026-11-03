<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Contracts\Attribute;
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
        array $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $clauses = [];

        foreach ($fields as $attribute) {
            $attribute = $this->attributeService->findAttributeByCode($attribute);
            if (! $attribute instanceof Attribute) {
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
             * Keyword fields: wildcard with a capped rewrite to avoid too_many_clauses.
             */
            $clauses[] = [
                'wildcard' => [
                    $attributePath => [
                        'value'            => '*'.strtolower($escapedValue).'*',
                        'case_insensitive' => true,
                        'rewrite'          => 'top_terms_1024',
                    ],
                ],
            ];
        }

        if ($clauses === []) {
            $this->queryBuilder->where(['match_none' => new \stdClass]);

            return $this;
        }

        $this->queryBuilder->where([
            'bool' => [
                'should'               => $clauses,
                'minimum_should_match' => 1,
            ],
        ]);

        return $this;
    }
}
