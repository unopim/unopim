<?php

namespace Webkul\DataTransfer\Enums;

enum CompletenessCondition: string
{
    case NONE = 'none';
    case AT_LEAST_ONE = 'at_least_one';
    case ALL = 'all';

    /**
     * A product/channel/locale row at this score is considered complete.
     */
    public const COMPLETE_SCORE = 100;

    /**
     * Allowed values for the completeness filter, usable in an `in:` rule.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
