<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\QueryString;

/**
 * Default filter for an Elasticsearch query can be used for all attributes mapped as keyword
 */
class DefaultFilter extends AbstractElasticSearchAttributeFilter
{
    /**
     * @param  array  $supportedProperties
     */
    public function __construct(
        array $supportedAttributeTypes = [Attribute::GALLERY_ATTRIBUTE_TYPE, Attribute::IMAGE_ATTRIBUTE_TYPE, Attribute::FILE_ATTRIBUTE_TYPE],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::CONTAINS]
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
                $clauses = [];

                foreach ((array) $value as $val) {
                    $escapedValue = QueryString::escapeValue($val);
                    $clauses[] = [
                        'wildcard' => [
                            $attributePath => '*'.$escapedValue.'*',
                        ],
                    ];
                }

                $clause = [
                    'bool' => [
                        'should'               => $clauses,
                        'minimum_should_match' => 1,
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;
        }

        return $this;
    }
}
