<?php

namespace Webkul\Product\Builders;

use Webkul\ElasticSearch\Contracts\QueryBuilder as QueryBuilderContract;

abstract class AbstractFilterableQueryBuilder implements QueryBuilderContract
{
    /** @var mixed */
    protected $qb;

    protected array $rawFilters = [];

    abstract public function prepareQueryBuilder();

    abstract public function applyFilter($property, $operator, $value, array $context = []);

    /**
     * {@inheritdoc}
     */
    public function setQueryManager($queryBuilder)
    {
        $this->qb = $queryBuilder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryManager()
    {
        if ($this->qb === null) {
            throw new \LogicException('Query manager must be configured');
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
    protected function applyPropertyFilter($filter, $property, $operator, $value, array $context)
    {
        $filter->setQueryManager($this->getQueryManager());

        if (! $filter->isOperatorAllowed($operator)) {
            throw new \InvalidArgumentException(
                sprintf(
                    implode(',', array_map(fn ($allowOperator) => $allowOperator->value, $filter->getAllowedOperators())),
                    $operator->value,
                )
            );
        }

        $filter->applyPropertyFilter($property, $operator, $value, $context);

        return $this;
    }
}
