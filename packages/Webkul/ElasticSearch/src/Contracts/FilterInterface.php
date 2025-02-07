<?php

namespace Webkul\ElasticSearch\Contracts;

interface FilterInterface
{
    /**
     * Inject the query builder
     */
    public function setQueryBuilder($queryBuilder);

    /**
     * This filter supports the operator
     *
     * @param string $operator
     *
     * @return bool
     */
    public function supportsOperator($operator);

    /**
     * Filter operators
     *
     * @return array
     */
    public function getOperators();
}