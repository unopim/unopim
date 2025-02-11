<?php

namespace Webkul\Attribute\Contracts;

interface AttributeNormalizer
{
    /**
     * Normalize the given attribute value.
     *
     * @return mixed
     */
    public function normalize(mixed $value, ?Attribute $attribute = null, array $options = []);
}
