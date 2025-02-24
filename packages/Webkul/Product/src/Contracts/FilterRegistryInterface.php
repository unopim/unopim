<?php

namespace Webkul\Product\Contracts;

/**
 * Register filters useable on product query builder
 */
interface FilterRegistryInterface
{
    /**
     * Get the property filter
     */
    public function getPropertyFilter($property, $operator);

    /**
     * Get the attribute filter
     */
    public function getAttributeFilter($attribute, $operator);

    /**
     * Returns all property filters
     */
    public function getPropertyFilters();

    /**
     * Returns all attribute filters
     */
    public function getAttributeFilters();
}
