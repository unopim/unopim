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
             * Every remaining type the indexer does not map as text, float or
             * date lands on the fallback dynamic template, which maps it as a
             * keyword (see ProductIndexer::dynamicAttributeMappings()); the
             * option backed types are the ones that reach this in practice.
             * rewrite: 'top_terms_1024' caps internal clause expansion and
             * avoids a too_many_clauses error on large indexes. The product
             * grid never gets here, because its quick search only ever looks
             * in text attributes; only a direct caller of the filter does.
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
            /**
             * An empty `should` is not an empty result set. Elasticsearch
             * builds the Lucene BooleanQuery first and answers a bool query
             * that ended up with no clause at all with a MatchAllDocsQuery,
             * before it looks at minimum_should_match -- which it only
             * applies when there are should clauses to count. Emitting the
             * empty bool would therefore answer a term that cannot be
             * evaluated with the entire catalogue. match_none refuses it, the
             * way the `1 = 0` of the database filter does.
             */
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
