<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Illuminate\Support\Str;

class LowerFilter extends TextFilter
{
    /**
     * Convert the value to lowercase.
     */
    public function apply(string $value, ?string $argument, string $locale): ?string
    {
        return Str::lower($value);
    }
}
