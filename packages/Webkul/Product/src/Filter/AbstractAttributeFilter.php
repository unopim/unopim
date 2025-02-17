<?php

namespace Webkul\Product\Filter;

abstract class AbstractAttributeFilter extends AbstractFilter
{
    /** @var string[] */
    protected $supportedAttributeTypes;

    abstract protected function getAttributePath($attribute, ?string $locale = null, ?string $channel = null);

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute->type, $this->supportedAttributeTypes);
    }
}
