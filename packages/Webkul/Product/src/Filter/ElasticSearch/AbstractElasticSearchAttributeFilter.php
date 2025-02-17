<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractElasticSearchAttributeFilter extends AbstractAttributeFilter
{
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
