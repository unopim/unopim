<?php

namespace Webkul\Product\Builders;

use Webkul\ElasticSearch\Contracts\QueryBuilder as QueryBuilderContract;

abstract class AbstractFilterableQueryBuilder implements QueryBuilderContract
{
    protected mixed $qb;

    protected array $rawFilters = [];

    abstract public function prepareQueryBuilder(): static;

    abstract public function applyFilter(mixed $property, mixed $operator, mixed $value, array $context = []): static;

    /**
     * {@inheritdoc}
     */
    public function setQueryManager($queryBuilder): static
    {
        $this->qb = $queryBuilder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryManager(): mixed
    {
        if ($this->qb === null) {
            throw new \LogicException('Query manager must be configured');
        }

        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawFilters(): array
    {
        return $this->rawFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function addSorter($property, $direction, array $context = []): static
    {
        $this->qb->addSort($property, $direction, $context);

        return $this;
    }

    /**
     * Add a filter condition on a property
     */
    protected function applyPropertyFilter(mixed $filter, mixed $property, mixed $operator, mixed $value, array $context): static
    {
        $filter->setQueryManager($this->getQueryManager());

        if (! $filter->isOperatorAllowed($operator)) {
            throw new \InvalidArgumentException(
                sprintf(
                    implode(',', array_map(fn (mixed $allowOperator) => $allowOperator->value, $filter->getAllowedOperators())),
                    $operator->value,
                )
            );
        }

        $filter->applyPropertyFilter($property, $operator, $value, $context);

        return $this;
    }
}
