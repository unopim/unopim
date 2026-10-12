<?php

namespace Webkul\DataTransfer\Enums;

enum CompletenessCondition: string
{
    case NONE = 'none';
    case AT_LEAST_ONE = 'at_least_one';
    case ALL = 'all';

    public const COMPLETE_SCORE = 100;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
