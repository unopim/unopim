<?php

namespace Webkul\Product\Enums;

enum ProductTypeEnum: string
{
    case Simple = 'simple';

    case Configurable = 'configurable';

    case VariantGroup = 'variant_group';

    public function label(): string
    {
        return trans('product::app.type.'.str_replace('_', '-', $this->value));
    }

    public function isVariantParent(): bool
    {
        return $this === self::Configurable || $this === self::VariantGroup;
    }

    public function isVariantChild(): bool
    {
        return $this === self::VariantGroup || $this === self::Simple;
    }

    public function variantLevel(): VariantLevelEnum
    {
        return match ($this) {
            self::Configurable => VariantLevelEnum::Common,
            self::VariantGroup => VariantLevelEnum::SubParent,
            self::Simple       => VariantLevelEnum::Variant,
        };
    }

    public static function variantParentValues(): array
    {
        return [self::Configurable->value, self::VariantGroup->value];
    }

    public static function variantChildValues(): array
    {
        return [self::VariantGroup->value, self::Simple->value];
    }
}
