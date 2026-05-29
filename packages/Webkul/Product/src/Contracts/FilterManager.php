<?php

declare(strict_types=1);

namespace Webkul\Product\Contracts;

use Illuminate\Support\Collection;

/**
 * Manager filters useable on product query builder
 */
interface FilterManager
{
    /**
     * Get the property filter
     */
    public function getPropertyFilter(mixed $property, mixed $operator): mixed;

    /**
     * Get the attribute filter
     */
    public function getAttributeFilter(mixed $attribute, mixed $operator): mixed;

    /**
     * Returns all property filters
     */
    public function getPropertyFilters(): Collection;

    /**
     * Returns all attribute filters
     */
    public function getAttributeFilters(): Collection;
}
