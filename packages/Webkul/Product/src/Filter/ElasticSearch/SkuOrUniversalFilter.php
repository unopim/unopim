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
                $attributePath .= '.keyword';
            }

            $clauses[] = [
                'wildcard' => [
                    $attributePath => '*'.$escapedValue.'*',
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
