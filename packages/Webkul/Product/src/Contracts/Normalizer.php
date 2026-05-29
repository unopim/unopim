<?php

declare(strict_types=1);

namespace Webkul\Product\Contracts;

interface Normalizer
{
    /**
     * Normalize the given attribute value.
     */
    public function normalize(mixed $value, array $options = []): ?array;
}
