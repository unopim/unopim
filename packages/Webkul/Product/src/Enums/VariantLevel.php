<?php

namespace Webkul\Product\Enums;

enum VariantLevel: string
{
    case Common = 'common';

    case SubParent = 'sub_parent';

    case Variant = 'variant';

    public const array VALUES = [
        self::Common->value,
        self::SubParent->value,
        self::Variant->value,
    ];

    public const array ORDER = [
        self::Common->value    => 0,
        self::SubParent->value => 1,
        self::Variant->value   => 2,
    ];

    public function order(): int
    {
        return self::ORDER[$this->value];
    }
}
