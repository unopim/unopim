<?php

namespace Webkul\Admin\Filters;

use Webkul\ElasticSearch\Enums\FilterOperators;

class ProductFilterOperators
{
    /**
     * Operators available for an attribute type.
     *
     * @return array<int, array{operator: FilterOperators, label: string}>
     */
    public static function forType(?string $type): array
    {
        $operators = self::groupForType($type)['operators'] ?? [];

        if (! is_array($operators)) {
            return [];
        }

        $mapped = array_map(function ($entry) {
            if (! is_array($entry)) {
                return null;
            }

            $value = $entry['operator'] ?? null;

            $operator = is_string($value) ? FilterOperators::tryFrom($value) : null;

            $label = $entry['label'] ?? '';

            return $operator
                ? ['operator' => $operator, 'label' => is_string($label) ? $label : '']
                : null;
        }, $operators);

        return array_values(array_filter($mapped));
    }

    public static function valueControl(?string $type, FilterOperators $operator): string
    {
        $valueless = self::config('valueless_operators', []);

        if (is_array($valueless) && in_array($operator->value, $valueless, true)) {
            return 'none';
        }

        $group = self::groupForType($type);

        if ($operator === FilterOperators::RANGE) {
            $rangeControl = $group['range_control'] ?? null;

            if (is_string($rangeControl) && $rangeControl !== '') {
                return $rangeControl;
            }
        }

        $control = $group['control'] ?? 'text';

        return is_string($control) ? $control : 'text';
    }

    /**
     * Operators for one attribute type, shaped for the datagrid's Vue components.
     *
     * @return array<int, array{value: string, label: string, control: string}>
     */
    public static function optionsForType(?string $type): array
    {
        return array_map(function (array $entry) use ($type) {
            $label = trans('admin::app.settings.data-transfer.exports.create.operators.'.$entry['label']);

            return [
                'value'   => $entry['operator']->value,
                'label'   => is_string($label) ? $label : $entry['label'],
                'control' => self::valueControl($type, $entry['operator']),
            ];
        }, self::forType($type));
    }

    /**
     * Every attribute type mapped to its operators, for the datagrid component.
     *
     * @return array<string, array<int, array{value: string, label: string, control: string}>>
     */
    public static function frontendMap(): array
    {
        $map = [];

        foreach (self::knownTypes() as $type) {
            $map[$type] = self::optionsForType($type);
        }

        return $map;
    }

    /**
     * The config group whose `types` include the given attribute type,
     * falling back to the configured default group.
     *
     * @return array<array-key, mixed>
     */
    protected static function groupForType(?string $type): array
    {
        $groups = self::config('groups', []);

        if (! is_array($groups)) {
            return [];
        }

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $types = $group['types'] ?? [];

            if (is_array($types) && in_array($type, $types, true)) {
                return $group;
            }
        }

        $default = self::config('default_group', 'text');

        $fallback = is_string($default) ? ($groups[$default] ?? []) : [];

        return is_array($fallback) ? $fallback : [];
    }

    /**
     * All attribute types that have a configured operator group.
     *
     * @return array<int, string>
     */
    protected static function knownTypes(): array
    {
        $groups = self::config('groups', []);

        if (! is_array($groups)) {
            return [];
        }

        $types = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $groupTypes = $group['types'] ?? [];

            if (! is_array($groupTypes)) {
                continue;
            }

            foreach ($groupTypes as $type) {
                if (is_string($type)) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    protected static function config(string $key, mixed $default = null): mixed
    {
        return config('product_filter_operators.'.$key, $default);
    }
}
