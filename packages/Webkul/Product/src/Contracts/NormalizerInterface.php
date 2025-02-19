<?php

namespace Webkul\Product\Contracts;

interface NormalizerInterface
{
    /**
     * Normalize the given attribute value.
     *
     * @return mixed
     */
    public function normalize(mixed $value, array $options = []): ?array;
}
