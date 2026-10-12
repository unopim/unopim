<?php

namespace Webkul\DataTransfer\Enums;

enum TimeCondition: string
{
    case NONE = 'none';
    case LAST_N_DAYS = 'last_n_days';
    case SINCE_LAST_EXPORT = 'since_last_export';
    case BETWEEN_DATES = 'between_dates';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
