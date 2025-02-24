<?php

namespace Webkul\ElasticSearch\Contracts;

interface PropertyFilterInterface extends FilterInterface
{
    /**
     * Add an attribute to filter
     */
    public function addPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []);

    /**
     * This filter supports the property
     */
    public function supportsProperty($property);

    /**
     * Returns supported properties
     */
    public function getProperties();
}
