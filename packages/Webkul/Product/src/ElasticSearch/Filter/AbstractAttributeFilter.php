<?php

namespace Webkul\Product\ElasticSearch\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter;

abstract class AbstractAttributeFilter extends AbstractFilter
{
    
/**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute->type, ['text']);
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function supportsOperator($operator)
    // {
    //     return in_array($operator, $this->supportedOperators);
    // }

    protected function getAttributePath($attribute)
    {
        return sprintf('values.%s.%s', $attribute->getScope(), $attribute->code);
    }
}