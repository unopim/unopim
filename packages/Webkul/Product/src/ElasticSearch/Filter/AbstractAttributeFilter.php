<?php

namespace Webkul\Product\ElasticSearch\Filter;

use Webkul\ElasticSearch\Filter\AbstractFilter;

abstract class AbstractAttributeFilter extends AbstractFilter
{
    /** @var string[] */
    protected $supportedAttributeTypes;

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute->type, $this->supportedAttributeTypes);
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function supportsOperator($operator)
    // {
    //     return in_array($operator, $this->supportedOperators);
    // }

    protected function getAttributePath($attribute, ?string $locale = null, ?string $channel = null)
    {
        return sprintf('values.%s.%s', $attribute->getScope($locale, $channel), $attribute->code);
    }
}
