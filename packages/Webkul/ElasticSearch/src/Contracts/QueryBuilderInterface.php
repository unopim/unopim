<?php

namespace Webkul\ElasticSearch\Contracts;

use Webkul\ElasticSearch\SearchQueryBuilder;

interface QueryBuilderInterface
{
    /**
     * Get query builder
     *
     * @throws \LogicException in case the query builder has not been configured
     */
    public function getQueryBuilder();
    

    /**
     * Set query builder
     */
    public function setQueryBuilder($queryBuilder);

    /**
     * Returns applied filters
     *
     * @return array
     */
    public function getRawFilters();


    /**
     * Sort by field
     *
     * @param string $field     the field to sort on
     * @param string $direction the direction to use
     * @param array  $context   the sorter context, used for locale and scope
     *
     * @throws \LogicException
     *
     * @return QueryBuilderInterface
     */
    public function addSorter($field, $direction, array $context = []);
}