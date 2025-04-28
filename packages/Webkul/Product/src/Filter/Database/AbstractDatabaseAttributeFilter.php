<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractDatabaseAttributeFilter extends AbstractAttributeFilter
{
    protected function getScopedAttributePath($attribute, ?string $locale = null, ?string $channel = null)
    {
        return sprintf('$.%s.%s', $attribute->getScope($locale, $channel), $attribute->code);
    }

    protected function getSearchTablePath(array $options = [])
    {
        return $options['search_table_path'] ?? DB::getTablePrefix().'products.values';
    }
}
