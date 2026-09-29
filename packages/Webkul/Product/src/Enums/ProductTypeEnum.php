<?php

namespace Webkul\Product\Enums;

enum ProductTypeEnum: string
{
    case Simple = 'simple';

    case Configurable = 'configurable';

    case VariantGroup = 'variant_group';

    const VARIANT_PARENT_VALUES = [
        self::Configurable->value,
        self::VariantGroup->value,
    ];

    const VARIANT_CHILD_VALUES = [
        self::VariantGroup->value,
        self::Simple->value,
    ];

    const VARIANT_LEVEL_BY_TYPE = [
        self::Configurable->value => VariantLevelEnum::Common->value,
        self::VariantGroup->value => VariantLevelEnum::SubParent->value,
        self::Simple->value       => VariantLevelEnum::Variant->value,
    ];

    public function label(): string
    {
        return trans('product::app.type.'.str_replace('_', '-', $this->value));
    }

    public function isVariantParent(): bool
    {
        return in_array($this->value, self::VARIANT_PARENT_VALUES, true);
    }

    public function isVariantChild(): bool
    {
        return in_array($this->value, self::VARIANT_CHILD_VALUES, true);
    }

    public function variantLevel(): VariantLevelEnum
    {
        return VariantLevelEnum::from(self::VARIANT_LEVEL_BY_TYPE[$this->value]);
    }
}
