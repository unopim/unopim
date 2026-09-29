<?php

namespace Webkul\Product\Enums;

enum VariantLevelEnum: string
{
    case Common = 'common';

    case SubParent = 'sub_parent';

    case Variant = 'variant';

    const VALUES = [
        self::Common->value,
        self::SubParent->value,
        self::Variant->value,
    ];

    const ORDER = [
        self::Common->value    => 0,
        self::SubParent->value => 1,
        self::Variant->value   => 2,
    ];

    public function order(): int
    {
        return self::ORDER[$this->value];
    }

    public function isInheritedBy(self $level): bool
    {
        return $this->order() <= $level->order();
    }
}
