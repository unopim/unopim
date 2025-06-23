<?php

namespace Webkul\ElasticSearch\Contracts;

interface PropertyFilter extends Filter
{
    /**
     * Add an attribute to filter
     */
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []);

    /**
     * This filter supports the property
     */
    public function supportsProperty($property);

    /**
     * Returns supported properties
     */
    public function getProperties();
}
