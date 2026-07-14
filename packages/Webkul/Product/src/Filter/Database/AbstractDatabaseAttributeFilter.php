<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractDatabaseAttributeFilter extends AbstractAttributeFilter
{
    /**
     * SQL comparison per FilterOperators value.
     */
    const COMPARISONS = [
        'lt'  => '<',
        'lte' => '<=',
        'gt'  => '>',
        'gte' => '>=',
    ];

    /**
     * Filter values arrive as arrays from the datagrid; comparisons need a scalar.
     */
    protected function scalarValue($value)
    {
        return is_array($value) ? reset($value) : $value;
    }

    protected function getScopedAttributePath($attribute, ?string $locale = null, ?string $channel = null)
    {
        return explode('.', $attribute->getScope($locale, $channel).'.'.$attribute->code);
    }

    protected function getSearchTablePath(array $options = [])
    {
        return $options['search_table_path'] ?? DB::getTablePrefix().'products.values';
    }
}
