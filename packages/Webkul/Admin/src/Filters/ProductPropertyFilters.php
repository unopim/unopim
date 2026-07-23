<?php

namespace Webkul\Admin\Filters;

/**
 * Product properties that are filterable but not part of the grid's default filter set.
 *
 * They are offered through the datagrid's "Add Filter" picker and share their definition
 * between the picker endpoint and the datagrid, so both agree on type and operators.
 */
class ProductPropertyFilters
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const COMPLETENESS = 'completeness';

    public const CATEGORIES = 'categories';

    /**
     * Column definitions keyed by index.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::CREATED_AT => self::dateColumn(
                self::CREATED_AT,
                trans('admin::app.catalog.products.index.datagrid.created-at')
            ),

            self::UPDATED_AT => self::dateColumn(
                self::UPDATED_AT,
                trans('admin::app.catalog.products.index.datagrid.updated-at')
            ),

            self::COMPLETENESS => [
                'index'          => self::COMPLETENESS,
                'label'          => trans('completeness::app.catalog.products.index.datagrid.completeness'),
                'filter_label'   => trans('completeness::app.components.layouts.sidebar.completeness'),
                'type'           => 'integer',
                'attribute_type' => 'integer',
                'operators'      => ProductFilterOperators::optionsForType('integer'),
                'searchable'     => false,
                'filterable'     => true,
                'sortable'       => true,
                'default_filter' => false,
            ],

            self::CATEGORIES => [
                'index'          => self::CATEGORIES,
                'label'          => trans('admin::app.catalog.products.index.datagrid.categories'),
                'type'           => 'dropdown',
                'attribute_type' => 'multiselect',
                'operators'      => ProductFilterOperators::optionsForType('multiselect'),
                'options'        => [
                    'type'     => 'searchable',
                    'route'    => route('admin.catalog.options.fetch-all'),
                    'track_by' => 'code',
                    'label_by' => 'label',
                    'params'   => [
                        'entityName' => 'category',
                    ],
                ],
                'searchable'     => false,
                'filterable'     => true,
                'sortable'       => false,
                'default_filter' => false,
            ],
        ];
    }

    /**
     * Definitions shaped for the datagrid filter picker: never rendered as grid columns.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pickerOptions(string $search = ''): array
    {
        $columns = array_map(
            fn (array $column): array => $column + ['visible' => false],
            array_values(self::all())
        );

        $search = trim(mb_strtolower($search));

        if ($search === '') {
            return $columns;
        }

        return array_values(array_filter(
            $columns,
            fn (array $column): bool => str_contains(mb_strtolower((string) $column['label']), $search)
                || str_contains($column['index'], $search)
        ));
    }

    public static function has(string $index): bool
    {
        return array_key_exists($index, self::all());
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $index): ?array
    {
        return self::all()[$index] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function dateColumn(string $index, string $label): array
    {
        return [
            'index'          => $index,
            'label'          => $label,
            'type'           => 'date_range',
            'attribute_type' => 'datetime',
            'operators'      => ProductFilterOperators::optionsForType('datetime'),
            'searchable'     => false,
            'filterable'     => true,
            'sortable'       => true,
            'default_filter' => false,
        ];
    }
}
