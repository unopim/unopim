<?php

namespace Webkul\Product\Filter;

abstract class AbstractFieldFilter extends AbstractFilter
{
    /** @var array */
    protected $supportedFields = [];

    /**
     * {@inheritdoc}
     */
    public function supportsField($field)
    {
        return in_array($field, $this->supportedFields);
    }
}
