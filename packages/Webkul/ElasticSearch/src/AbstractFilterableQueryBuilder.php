<?php

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\QueryBuilderInterface;
use Webkul\ElasticSearch\Facades\SearchQuery;

abstract class AbstractFilterableQueryBuilder implements QueryBuilderInterface
{
    /** @var mixed */
    protected $qb;

    protected array $rawFilters = [];

    abstract public function prepareQueryBuilder();

    abstract public function addFilter($property, $operator, $value, array $context = []);

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
    public function addSorter($property, $direction, array $context = [])
    {
        $this->qb->addSort($property, $direction, $context);

        return $this;
    }

    /**
     * Add a filter condition on a property
     */
    protected function addPropertyFilter($filter, $property, $operator, $value, array $context)
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

        $filter->addPropertyFilter($property, $operator, $value, $context);

        return $this;
    }
}
