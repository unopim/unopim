<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Filter\AbstractAttributeFilter;

abstract class AbstractDatabaseAttributeFilter extends AbstractAttributeFilter
{
    const COMPARISONS = [
        'lt'  => '<',
        'lte' => '<=',
        'gt'  => '>',
        'gte' => '>=',
    ];

    /**
     * A database filter always works against a database query builder; the
     * inherited guard would instead demand an ElasticSearchQuery whenever the
     * elasticsearch config flag happens to be on.
     */
    public function setQueryManager($queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    protected function scalarValue(mixed $value): mixed
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
