<?php

declare(strict_types=1);

namespace Webkul\Product\Filter;

abstract class AbstractAttributeFilter extends AbstractFilter
{
    /** @var string[] */
    protected array $supportedAttributeTypes;

    abstract protected function getScopedAttributePath(mixed $attribute, ?string $locale = null, ?string $channel = null): mixed;

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute(mixed $attribute): bool
    {
        return in_array($attribute->type, $this->supportedAttributeTypes);
    }
}
