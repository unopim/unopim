<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Filters;

use Webkul\Attribute\Models\Attribute;

class AttributeConditionOperators
{
    const IN = 'in';

    const NOT_IN = 'not_in';

    const EMPTY = 'empty';

    const NOT_EMPTY = 'not_empty';

    const CONTAINS = 'contains';

    const EQUALS = 'equals';

    const LESS_THAN = 'less_than';

    const LESS_THAN_EQUAL = 'less_than_equal';

    const GREATER_THAN = 'greater_than';

    const GREATER_THAN_EQUAL = 'greater_than_equal';

    const BETWEEN = 'between';

    const BEFORE = 'before';

    const AFTER = 'after';

    const INTEGER_TYPE = 'integer';

    const DECIMAL_TYPE = 'decimal';

    const OPTION_TYPES = [
        Attribute::SELECT_FIELD_TYPE,
        Attribute::MULTISELECT_FIELD_TYPE,
        Attribute::CHECKBOX_FIELD_TYPE,
    ];

    const NUMERIC_TYPES = [
        Attribute::PRICE_FIELD_TYPE,
        self::INTEGER_TYPE,
        self::DECIMAL_TYPE,
    ];

    const DATE_TYPES = [
        Attribute::DATE_FIELD_TYPE,
        Attribute::DATETIME_FIELD_TYPE,
    ];

    const KNOWN_TYPES = [
        Attribute::TEXT_TYPE,
        Attribute::TEXTAREA_TYPE,
        Attribute::BOOLEAN_FIELD_TYPE,
        Attribute::PRICE_FIELD_TYPE,
        self::INTEGER_TYPE,
        self::DECIMAL_TYPE,
        Attribute::SELECT_FIELD_TYPE,
        Attribute::MULTISELECT_FIELD_TYPE,
        Attribute::CHECKBOX_FIELD_TYPE,
        Attribute::DATE_FIELD_TYPE,
        Attribute::DATETIME_FIELD_TYPE,
    ];

    public static function forType(?string $type): array
    {
        return match (true) {
            in_array($type, self::OPTION_TYPES, true)  => [self::IN, self::NOT_IN, self::EMPTY, self::NOT_EMPTY],
            $type === Attribute::BOOLEAN_FIELD_TYPE    => [self::EQUALS],
            in_array($type, self::NUMERIC_TYPES, true) => [self::EQUALS, self::LESS_THAN, self::LESS_THAN_EQUAL, self::GREATER_THAN, self::GREATER_THAN_EQUAL, self::BETWEEN, self::EMPTY, self::NOT_EMPTY],
            in_array($type, self::DATE_TYPES, true)    => [self::BEFORE, self::AFTER, self::BETWEEN, self::EMPTY, self::NOT_EMPTY],
            default                                    => [self::CONTAINS, self::EQUALS, self::EMPTY, self::NOT_EMPTY],
        };
    }

    public static function valueControl(?string $type, string $operator): string
    {
        if (in_array($operator, [self::EMPTY, self::NOT_EMPTY], true)) {
            return 'none';
        }

        if ($type === Attribute::BOOLEAN_FIELD_TYPE) {
            return 'boolean';
        }

        if (in_array($type, self::OPTION_TYPES, true)) {
            return 'options';
        }

        if ($operator === self::BETWEEN) {
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

    public static function frontendMap(): array
    {
        $map = [];

        foreach (self::KNOWN_TYPES as $type) {
            $map[$type] = array_map(fn ($operator) => [
                'value'   => $operator,
                'label'   => trans('admin::app.settings.data-transfer.exports.create.operators.'.$operator),
                'control' => self::valueControl($type, $operator),
            ], self::forType($type));
        }

        return $map;
    }
}
