<?php

namespace Webkul\DataTransfer\Enums;

enum ProductStatusFilter: string
{
    case ENABLE = 'enable';
    case DISABLE = 'disable';
    case ALL = 'all';

    /**
     * Allowed values for the status filter, usable in an `in:` validation rule.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
