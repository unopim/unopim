<?php

namespace Webkul\Admin\Filters;

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

/**
 * Operators offered by the product datagrid's attribute filters.
 *
 * Values are FilterOperators cases, so the datagrid can hand what the user picked
 * straight to the query builder. Only operators the product filters implement are
 * listed here — anything else would resolve to no filter at query time.
 */
class ProductFilterOperators
{
    const OPTION_TYPES = [
        Attribute::SELECT_FIELD_TYPE,
        Attribute::MULTISELECT_FIELD_TYPE,
        Attribute::CHECKBOX_FIELD_TYPE,
    ];

    const NUMERIC_TYPES = [
        Attribute::PRICE_FIELD_TYPE,
        'integer',
        'decimal',
    ];

    const DATE_TYPES = [
        Attribute::DATE_FIELD_TYPE,
        Attribute::DATETIME_FIELD_TYPE,
    ];

    const TEXT_TYPES = [
        Attribute::TEXT_TYPE,
        Attribute::TEXTAREA_TYPE,
    ];

    /**
     * Every type the picker can surface, so the frontend gets a complete map.
     */
    const KNOWN_TYPES = [
        ...self::TEXT_TYPES,
        ...self::NUMERIC_TYPES,
        ...self::OPTION_TYPES,
        ...self::DATE_TYPES,
        Attribute::BOOLEAN_FIELD_TYPE,
    ];

    /**
     * Operators available for an attribute type.
     *
     * @return array<int, array{operator: FilterOperators, label: string}>
     */
    public static function forType(?string $type): array
    {
        if (in_array($type, self::OPTION_TYPES, true)) {
            return [
                [FilterOperators::IN, 'in'],
                [FilterOperators::NOT_IN, 'not_in'],
                [FilterOperators::IS_EMPTY, 'empty'],
                [FilterOperators::IS_NOT_EMPTY, 'not_empty'],
            ];
        }

        if ($type === Attribute::BOOLEAN_FIELD_TYPE) {
            return [
                [FilterOperators::EQUAL, 'equals'],
            ];
        }

        if (in_array($type, self::NUMERIC_TYPES, true)) {
            return [
                [FilterOperators::EQUAL, 'equals'],
                [FilterOperators::LESS_THAN, 'less_than'],
                [FilterOperators::LESS_THAN_OR_EQUAL, 'less_than_equal'],
                [FilterOperators::GREATER_THAN, 'greater_than'],
                [FilterOperators::GREATER_THAN_OR_EQUAL, 'greater_than_equal'],
                [FilterOperators::RANGE, 'between'],
                [FilterOperators::IS_EMPTY, 'empty'],
                [FilterOperators::IS_NOT_EMPTY, 'not_empty'],
            ];
        }

        if (in_array($type, self::DATE_TYPES, true)) {
            return [
                [FilterOperators::LESS_THAN, 'before'],
                [FilterOperators::GREATER_THAN, 'after'],
                [FilterOperators::RANGE, 'between'],
                [FilterOperators::IS_EMPTY, 'empty'],
                [FilterOperators::IS_NOT_EMPTY, 'not_empty'],
            ];
        }

        return [
            [FilterOperators::CONTAINS, 'contains'],
            [FilterOperators::EQUAL, 'equals'],
            [FilterOperators::IS_EMPTY, 'empty'],
            [FilterOperators::IS_NOT_EMPTY, 'not_empty'],
        ];
    }

    /**
     * Which value input the operator needs.
     */
    public static function valueControl(?string $type, FilterOperators $operator): string
    {
        if (in_array($operator, [FilterOperators::IS_EMPTY, FilterOperators::IS_NOT_EMPTY], true)) {
            return 'none';
        }

        if ($type === Attribute::BOOLEAN_FIELD_TYPE) {
            return 'boolean';
        }

        if (in_array($type, self::OPTION_TYPES, true)) {
            return 'options';
        }

        if ($operator === FilterOperators::RANGE) {
            return in_array($type, self::DATE_TYPES, true) ? 'date_range' : 'number_range';
        }

        if (in_array($type, self::DATE_TYPES, true)) {
            return 'date';
        }

        if (in_array($type, self::NUMERIC_TYPES, true)) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Operators for one attribute type, shaped for the datagrid's Vue components.
     *
     * @return array<int, array{value: string, label: string, control: string}>
     */
    public static function optionsForType(?string $type): array
    {
        return array_map(fn ($entry) => [
            'value'   => $entry[0]->value,
            'label'   => trans('admin::app.settings.data-transfer.exports.create.operators.'.$entry[1]),
            'control' => self::valueControl($type, $entry[0]),
        ], self::forType($type));
    }

    /**
     * Every attribute type mapped to its operators, for the datagrid component.
     */
    public static function frontendMap(): array
    {
        $map = [];

        foreach (self::KNOWN_TYPES as $type) {
            $map[$type] = self::optionsForType($type);
        }

        return $map;
    }
}
