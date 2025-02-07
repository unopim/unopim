<?php

namespace Webkul\ElasticSearch\Filter;

use Webkul\ElasticSearch\Contracts\FilterInterface;

use Webkul\ElasticSearch\Facades\SearchQuery;

abstract class AbstractFilter implements FilterInterface
{
    /** @var SearchQuery */
    protected $searchQueryBuilder = null;

    /** @var array */
    protected $supportedOperators = [];

    /**
     * {@inheritdoc}
     */
    public function supportsOperator($operator)
    {
        return in_array($operator, $this->supportedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return $this->supportedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryBuilder($searchQueryBuilder)
    {
        if (!$searchQueryBuilder instanceof SearchQuery) {
            throw new \InvalidArgumentException(
                sprintf('Query builder should be an instance of "%s"', SearchQuery::class)
            );
        }

        $this->searchQueryBuilder = $searchQueryBuilder;
    }
}
