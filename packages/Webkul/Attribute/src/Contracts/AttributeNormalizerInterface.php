<?php

declare(strict_types=1);

namespace Webkul\Attribute\Contracts;

interface AttributeNormalizerInterface
{
    /**
     * Normalize the given attribute value.
     */
    public function normalize(mixed $value, ?Attribute $attribute = null, array $options = []): mixed;
}
