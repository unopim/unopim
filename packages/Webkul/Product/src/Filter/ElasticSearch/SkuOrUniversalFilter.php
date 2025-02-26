<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\QueryString;

/**
 * Sku or name filter for an Elasticsearch query
 */
class SkuOrUniversalFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedAttributeTypes
     */
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

            $clauses[] = [
                'wildcard' => [
                    $attributePath.'.keyword' => '*'.$escapedValue.'*',
                ],
            ];

            $clauses[] = [
                'query_string' => [
                    'default_field'    => $attributePath,
                    'query'            => '*'.$escapedValue.'*',
                    'analyze_wildcard' => true,
                    'default_operator' => 'AND',
                    'fuzziness'        => 'AUTO',
                ],
            ];
        }

        $this->queryBuilder::where(['bool' => [
            'should'               => $clauses,
            'minimum_should_match' => 1,
        ],
        ]);

        return $this;
    }
}
