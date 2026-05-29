<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Contracts;

interface Filter
{
    /**
     * Inject the query builder
     */
    public function setQueryManager(mixed $queryBuilder): void;

    /**
     * This filter supports the operator
     *
     * @param  string  $operator
     */
    public function isOperatorAllowed(mixed $operator): bool;

    /**
     * Filter operators
     */
    public function getAllowedOperators(): array;
}
