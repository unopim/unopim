<?php

namespace Webkul\Product\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter as BaseAbstractFilter;

abstract class AbstractFilter extends BaseAbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function setQueryBuilder($searchQueryBuilder)
    {
        if (core()->isElasticsearchEnabled()) {
            parent::setQueryBuilder($searchQueryBuilder);

            return;
        }

        $this->searchQueryBuilder = $searchQueryBuilder;
    }
}
