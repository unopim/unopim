<?php

namespace Webkul\Product\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter as BaseAbstractFilter;

abstract class AbstractFilter extends BaseAbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function setQueryManager($queryBuilder)
    {
        if (core()->isElasticsearchEnabled()) {
            parent::setQueryManager($queryBuilder);

            return;
        }

        $this->queryBuilder = $queryBuilder;
    }
}
