<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Illuminate\Support\Str;

class UpperFilter extends TextFilter
{
    /**
     * Convert the value to uppercase.
     */
    public function apply(string $value, ?string $argument, string $locale): ?string
    {
        return Str::upper($value);
    }
}
