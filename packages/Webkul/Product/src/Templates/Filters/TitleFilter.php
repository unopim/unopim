<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Illuminate\Support\Str;

class TitleFilter extends TextFilter
{
    public function apply(string $value, ?string $argument, string $locale): ?string
    {
        return Str::title($value);
    }
}
