<?php

namespace Webkul\ElasticSearch\Contracts;

interface Filter
{
    /**
     * Inject the query builder
     */
    public function setQueryManager($queryBuilder);

    /**
     * This filter supports the operator
     *
     * @param  string  $operator
     * @return bool
     */
    public function isOperatorAllowed($operator);

    /**
     * Filter operators
     *
     * @return array
     */
    public function getAllowedOperators();
}
