<?php

namespace Webkul\Product\ElasticSearch\Filter\Field;

use Webkul\ElasticSearch\Filter\AbstractFilter;

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
