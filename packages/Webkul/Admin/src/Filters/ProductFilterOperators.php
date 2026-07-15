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

        return array_values(array_filter(array_map(function (array $entry) {
            $operator = FilterOperators::tryFrom($entry['operator'] ?? '');

            return $operator ? ['operator' => $operator, 'label' => $entry['label']] : null;
        }, $operators)));
    }

    public static function valueControl(?string $type, FilterOperators $operator): string
    {
        if (in_array($operator->value, self::config('valueless_operators', []), true)) {
            return 'none';
        }

        $group = self::groupForType($type);

        if ($operator === FilterOperators::RANGE && ! empty($group['range_control'])) {
            return $group['range_control'];
        }

        return $group['control'] ?? 'text';
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
     * @return array<string, mixed>
     */
    protected static function groupForType(?string $type): array
    {
        $groups = self::config('groups', []);

        foreach ($groups as $group) {
            if (in_array($type, $group['types'] ?? [], true)) {
                return $group;
            }
        }

        return $groups[self::config('default_group', 'text')] ?? [];
    }

    /**
     * All attribute types that have a configured operator group.
     *
     * @return array<int, string>
     */
    protected static function knownTypes(): array
    {
        $types = [];

        foreach (self::config('groups', []) as $group) {
            foreach ($group['types'] ?? [] as $type) {
                $types[] = $type;
            }
        }

        return $types;
    }

    protected static function config(string $key, mixed $default = null): mixed
    {
        return config('product_filter_operators.'.$key, $default);
    }
}
