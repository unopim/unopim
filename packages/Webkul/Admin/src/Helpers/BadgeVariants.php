<?php

namespace Webkul\Admin\Helpers;

/**
 * The colour classes of each `<x-admin::badge>` variant; the badge component and any
 * endpoint that re-colours a badge in place read this one map.
 */
class BadgeVariants
{
    /**
     * @var array<string, string>
     */
    private const array CLASSES = [
        'success' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
        'danger'  => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
        'info'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
        'neutral' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::CLASSES;
    }

    public static function classesFor(string $variant): string
    {
        return self::CLASSES[$variant] ?? self::CLASSES['neutral'];
    }
}
