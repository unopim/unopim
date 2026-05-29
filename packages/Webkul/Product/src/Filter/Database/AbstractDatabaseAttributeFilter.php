<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractDatabaseAttributeFilter extends AbstractAttributeFilter
{
    protected function getScopedAttributePath(mixed $attribute, ?string $locale = null, ?string $channel = null): array
    {
        return explode('.', $attribute->getScope($locale, $channel).'.'.$attribute->code);
    }

    protected function getSearchTablePath(array $options = []): string
    {
        return $options['search_table_path'] ?? DB::getTablePrefix().'products.values';
    }
}
