<?php

namespace Webkul\Product\ElasticSearch\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\ElasticSearch\QueryString;

/**
 * Text filter for an Elasticsearch query
 *
 */
class TextFilter extends AbstractAttributeFilter implements FilterInterface
{

    /**
     * @param array $supportedFields
     * @param array $supportedOperators
     */
    public function __construct(
        array $supportedOperators = []
    ) {
        $this->supportedOperators = $supportedOperators;
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
        if (null === $this->searchQueryBuilder) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        // $this->checkLocaleAndChannel($attribute, $locale, $channel);

        // if (Operators::IS_EMPTY !== $operator && Operators::IS_NOT_EMPTY !== $operator) {
        //     $this->checkValue($attribute, $value);
        // }

        $attributePath = $this->getAttributePath($attribute, $locale, $channel);
        
        switch ($operator) {
            case Operators::STARTS_WITH:
                $escapedValue = QueryString::escapeValue(current((array)$value));
                $clause = [
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => $escapedValue . '*',
                    ],
                ];
                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::CONTAINS:
                $escapedValue = QueryString::escapeValue(current((array)$value));
                $clause = [
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => '*' . $escapedValue . '*',
                    ],
                ];
                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::DOES_NOT_CONTAIN:
                $escapedValue = QueryString::escapeValue($value);
                $mustNotClause = [
                    'query_string' => [
                        'default_field' => $attributePath,
                        'query'         => '*' . $escapedValue . '*',
                    ],
                ];
                $filterClause = [
                    'exists' => ['field' => $attributePath],
                ];

                $this->searchQueryBuilder::addMustNot($mustNotClause);
                $this->searchQueryBuilder::addFilter($filterClause);
                break;

            case Operators::EQUALS:
                $clause = [
                    'term' => [
                        $attributePath => $value,
                    ],
                ];
                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::NOT_EQUAL:
                $mustNotClause = [
                    'term' => [
                        $attributePath => $value,
                    ],
                ];

                $filterClause = [
                    'exists' => [
                        'field' => $attributePath,
                    ],
                ];
                $this->searchQueryBuilder::addMustNot($mustNotClause);
                $this->searchQueryBuilder::addFilter($filterClause);
                break;

            case Operators::IS_EMPTY:
                $clause = [
                    'exists' => [
                        'field' => $attributePath,
                    ],
                ];
                $this->searchQueryBuilder::addMustNot($clause);
                break;

            case Operators::IS_NOT_EMPTY:
                $clause = [
                    'exists' => [
                        'field' => $attributePath,
                    ],
                ];
                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
