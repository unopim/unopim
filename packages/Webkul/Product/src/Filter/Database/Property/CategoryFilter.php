<?php

namespace Webkul\Product\Filter\Database\Property;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * Category filter for a database query.
 *
 * Variants show their parent's categories in the grid, so the parent's values are matched too.
 */
class CategoryFilter extends AbstractPropertyFilter
{
    const PROPERTY = 'categories';

    const PARENT_TABLE = 'parent_products';

    public function __construct(
        array $supportedProperties = [self::PROPERTY],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::NOT_IN,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
    ) {
        $this->allowedOperators = $allowedOperators;
        $this->supportedProperties = $supportedProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []): static
    {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        if (! in_array($property, $this->supportedProperties)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported property name for category filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        $codes = array_values(array_filter(array_map('strval', (array) $value), fn (string $code): bool => $code !== ''));

        match ($operator) {
            FilterOperators::IN     => $this->whereAnyCategory($codes, $options),
            FilterOperators::NOT_IN => $this->queryBuilder->whereNot(fn ($query) => $this->whereAnyCategory($codes, $options, $query)),
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY => $this->whereHasCategories($operator === FilterOperators::IS_NOT_EMPTY, $options),
            default                       => $this,
        };

        return $this;
    }

    /**
     * Match products carrying any of the given category codes.
     *
     * @param  array<int, string>  $codes
     */
    protected function whereAnyCategory(array $codes, array $options = [], mixed $query = null): void
    {
        $query ??= $this->queryBuilder;

        if (empty($codes)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $grammar = DB::rawQueryGrammar();

        $query->where(function ($query) use ($codes, $grammar, $options): void {
            foreach ($this->categoryColumns($options) as $column) {
                foreach ($codes as $code) {
                    $query->orWhereRaw(
                        $grammar->jsonContains($column, [self::PROPERTY], '?'),
                        [json_encode($code)]
                    );
                }
            }
        });
    }

    protected function whereHasCategories(bool $hasCategories, array $options = []): void
    {
        $grammar = DB::rawQueryGrammar();

        $condition = function ($query) use ($grammar, $options): void {
            foreach ($this->categoryColumns($options) as $column) {
                $path = $grammar->jsonExtract($column, self::PROPERTY);

                $query->orWhereRaw("COALESCE($path, '[]') NOT IN ('[]', '')");
            }
        };

        $hasCategories
            ? $this->queryBuilder->where($condition)
            : $this->queryBuilder->whereNot($condition);
    }

    /**
     * The product's own values column plus the parent's, so inherited categories match too.
     *
     * @return array<int, string>
     */
    protected function categoryColumns(array $options = []): array
    {
        $prefix = DB::getTablePrefix();

        return [
            $prefix.$this->getSearchTablePath($options).'.values',
            $prefix.self::PARENT_TABLE.'.values',
        ];
    }
}
