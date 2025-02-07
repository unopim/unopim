<?php

namespace Webkul\Product\Contracts;

/**
 * Register filters useable on product query builder
 */
interface FilterRegistryInterface
{
    /**
     * Get the filter (field or attribute)
     */
    public function getFilter($code, $operator);

    /**
     * Get the field filter
     */
    public function getFieldFilter($field, $operator);

    /**
     * Get the attribute filter
     */
    public function getAttributeFilter($attribute, $operator);

    /**
     * Returns all field filters
     */
    public function getFieldFilters();

    /**
     * Returns all attribute filters
     */
    public function getAttributeFilters();
}
