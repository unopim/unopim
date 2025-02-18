<?php

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\QueryBuilderInterface;
use Webkul\ElasticSearch\Facades\SearchQuery;

abstract class AbstractEntityQueryBuilder implements QueryBuilderInterface
{
    /** @var mixed */
    protected $qb;

    protected array $rawFilters = [];

    abstract public function prepareQueryBuilder();

    abstract public function addFilter($field, $operator, $value, array $context = []);

    /**
     * {@inheritdoc}
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->qb = $queryBuilder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder()
    {
        if ($this->qb === null) {
            throw new \LogicException('Query builder must be configured');
        }

        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawFilters()
    {
        return $this->rawFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function addSorter($field, $direction, array $context = [])
    {
        $this->qb->addSort($field, $direction, $context);

        return $this;
    }

    /**
     * Add a filter condition on a field
     */
    protected function addFieldFilter($filter, $field, $operator, $value, array $context)
    {
        $filter->setQueryBuilder(new SearchQuery);

        if (! $filter->supportsOperator($operator)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported operator. Only "%s" are supported, but "%s" was given.',
                    implode(',', $filter->getOperators()),
                    $operator
                )
            );
        }

        $filter->addFieldFilter($field, $operator, $value, $context);

        return $this;
    }
}
