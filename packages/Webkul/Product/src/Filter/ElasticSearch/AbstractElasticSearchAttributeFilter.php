<?php

namespace Webkul\Product\Filter\ElasticSearch;

use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractElasticSearchAttributeFilter extends AbstractAttributeFilter
{
    protected function getScopedAttributePath($attribute, ?string $locale = null, ?string $channel = null)
    {
        return sprintf('values.%s.%s', $attribute->getScope($locale, $channel), $attribute->code);
    }
}
