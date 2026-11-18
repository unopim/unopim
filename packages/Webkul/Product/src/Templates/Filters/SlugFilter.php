<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Illuminate\Support\Str;

class SlugFilter extends TextFilter
{
    /**
     * Convert the value to a URL slug using the locale's language.
     */
    public function apply(string $value, ?string $argument, string $locale): ?string
    {
        return Str::slug($value, '-', Str::before($locale, '_'));
    }
}
