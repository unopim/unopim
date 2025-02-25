<?php

namespace Webkul\ElasticSearch\Contracts;

interface QueryBuilder
{
    /**
     * Get query builder
     *
     * @throws \LogicException in case the query builder has not been configured
     */
    public function getQueryManager();

    /**
     * Set query builder
     */
    public function setQueryManager($queryBuilder);

    /**
     * Returns applied filters
     *
     * @return array
     */
    public function getRawFilters();

    /**
     * Sort by field
     *
     * @param  string  $field  the field to sort on
     * @param  string  $direction  the direction to use
     * @param  array  $context  the sorter context, used for locale and scope
     * @return QueryBuilder
     *
     * @throws \LogicException
     */
    public function addSorter($field, $direction, array $context = []);
}
