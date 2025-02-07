<?php

namespace Webkul\ElasticSearch\Contracts;

interface FieldFilterInterface extends FilterInterface
{
    /**
     * Add an attribute to filter
     */
    public function addFieldFilter($field, $operator, $value, $locale = null, $channel = null, $options = []);

    /**
     * This filter supports the field
     */
    public function supportsField($field);

    /**
     * Returns supported fields
     */
    public function getFields();
}