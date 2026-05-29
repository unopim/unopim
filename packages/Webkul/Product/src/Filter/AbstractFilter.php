<?php

declare(strict_types=1);

namespace Webkul\Product\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter as BaseAbstractFilter;

abstract class AbstractFilter extends BaseAbstractFilter
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function setQueryManager($queryBuilder): void
    {
        if (config('elasticsearch.enabled')) {
            parent::setQueryManager($queryBuilder);

            return;
        }

        $this->queryBuilder = $queryBuilder;
    }
}
