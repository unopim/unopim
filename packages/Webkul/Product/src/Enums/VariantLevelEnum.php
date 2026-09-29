<?php

namespace Webkul\Product\Enums;

enum VariantLevelEnum: string
{
    case Common = 'common';

    case SubParent = 'sub_parent';

    case Variant = 'variant';

    public function order(): int
    {
        return match ($this) {
            self::Common    => 0,
            self::SubParent => 1,
            self::Variant   => 2,
        };
    }

    public function isInheritedBy(self $level): bool
    {
        return $this->order() <= $level->order();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
