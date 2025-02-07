<?php

namespace Webkul\Product\ElasticSearch\Filter;

use Webkul\Attribute\Services\AttributeService; 
use Webkul\Product\Contracts\FilterRegistryInterface;
use Illuminate\Contracts\Container\Container;

class FilterRegistry implements FilterRegistryInterface
{
    protected $attributeFilters;

    protected $fieldFilters;

    public function __construct(Container $app)
    {
        $this->attributeFilters = collect($app->tagged('attribute.filters'));
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter($field, $operator, $value, array $context = [])
    {
        // $attribute = $this->attributeService->findAttributeByCode($field);

        // if (!$attribute) {
        //     $this->addFieldFilter($field, $operator, $value, $context);
        // } else {
        //     $this->addAttributeFilter($field, $operator, $value, $context);
        // }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter($code, $operator)
    {
        // $attribute = $this->attributeService->findAttributeByCode($code);

        // if (null !== $attribute) {
        //     return $this->getAttributeFilter($attribute, $operator);
        // }

        // return $this->getFieldFilter($code, $operator);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldFilter($field, $operator)
    {
        // foreach ($this->fieldFilters as $filter) {
        //     if ($filter->supportsField($field) && $filter->supportsOperator($operator)) {
        //         return $filter;
        //     }
        // }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeFilter($attribute, $operator)
    {
        foreach ($this->attributeFilters as $filter) {
            if ($filter->supportsAttribute($attribute)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldFilters()
    {
        return $this->fieldFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeFilters()
    {
        return $this->attributeFilters;
    }
}
