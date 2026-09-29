<?php

namespace Webkul\Product\Enums;

enum ProductType: string
{
    case Simple = 'simple';

    case Configurable = 'configurable';

    case VariantGroup = 'variant_group';

    public const array VARIANT_PARENT_VALUES = [
        self::Configurable->value,
        self::VariantGroup->value,
    ];

    public const array VARIANT_CHILD_VALUES = [
        self::VariantGroup->value,
        self::Simple->value,
    ];

    public const array VARIANT_LEVEL_BY_TYPE = [
        self::Configurable->value => VariantLevel::Common->value,
        self::VariantGroup->value => VariantLevel::SubParent->value,
        self::Simple->value       => VariantLevel::Variant->value,
    ];

    public function variantLevel(): VariantLevel
    {
        return VariantLevel::from(self::VARIANT_LEVEL_BY_TYPE[$this->value]);
    }
}
