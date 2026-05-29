<?php

namespace Webkul\Product\Filter;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Webkul\Product\Contracts\FilterManager as FilterManagerContract;
use Webkul\Product\Filter\Database\SkuOrUniversalFilter as DatabaseSkuOrUniversalFilter;
use Webkul\Product\Filter\ElasticSearch\SkuOrUniversalFilter;

class FilterManager implements FilterManagerContract
{
    protected Collection $attributeFilters;

    protected Collection $propertyFilters;

    public function __construct(Container $app)
    {
        $this->attributeFilters = config('elasticsearch.enabled')
            ? collect($app->tagged('unopim.elasticsearch.attribute.filters'))
            : collect($app->tagged('unopim.database.attribute.filters'));

        $this->propertyFilters = config('elasticsearch.enabled')
            ? collect($app->tagged('unopim.elasticsearch.product.property.filters'))
            : collect($app->tagged('unopim.database.product.property.filters'));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyFilter(mixed $property, mixed $operator): mixed
    {
        foreach ($this->propertyFilters as $filter) {
            if ($filter->supportsProperty($property)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeFilter(mixed $attribute, mixed $operator): mixed
    {
        foreach ($this->attributeFilters as $filter) {
            if ($filter->supportsAttribute($attribute)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Get filter sku or universal attribute filter.
     */
    public function getSkuOrUnfilteredFilter(): AbstractFilter
    {
        return config('elasticsearch.enabled')
           ? app(SkuOrUniversalFilter::class)
           : app(DatabaseSkuOrUniversalFilter::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyFilters(): Collection
    {
        return $this->propertyFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeFilters(): Collection
    {
        return $this->attributeFilters;
    }
}
