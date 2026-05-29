<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Contracts;

interface QueryBuilder
{
    /**
     * Get query builder
     *
     * @throws \LogicException in case the query builder has not been configured
     */
    public function getQueryManager(): mixed;

    /**
     * Set query builder
     */
    public function setQueryManager(mixed $queryBuilder): static;

    /**
     * Returns applied filters
     */
    public function getRawFilters(): array;

    /**
     * Sort by field
     *
     * @param  string  $field  the field to sort on
     * @param  string  $direction  the direction to use
     * @param  array  $context  the sorter context, used for locale and scope
     *
     * @throws \LogicException
     */
    public function addSorter(mixed $field, mixed $direction, array $context = []): static;
}
