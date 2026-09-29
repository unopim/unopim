<?php

use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Enums\ProductTypeEnum;
use Webkul\Product\Enums\VariantLevelEnum;

describe('ProductTypeEnum', function () {
    it('keeps the legacy string values stored in products.type', function () {
        expect(ProductTypeEnum::Simple->value)->toBe('simple')
            ->and(ProductTypeEnum::Configurable->value)->toBe('configurable')
            ->and(ProductTypeEnum::VariantGroup->value)->toBe('variant_group');
    });

    it('registers every case in the product_types config under its own value', function (ProductTypeEnum $type) {
        expect(config('product_types'))->toHaveKey($type->value)
            ->and(config('product_types.'.$type->value.'.key'))->toBe($type->value);
    })->with(ProductTypeEnum::cases());

    it('maps each type to the variant level it sits at', function (ProductTypeEnum $type, VariantLevelEnum $level) {
        expect($type->variantLevel())->toBe($level);
    })->with([
        [ProductTypeEnum::Configurable, VariantLevelEnum::Common],
        [ProductTypeEnum::VariantGroup, VariantLevelEnum::SubParent],
        [ProductTypeEnum::Simple, VariantLevelEnum::Variant],
    ]);

    it('knows which types are variant parents and which are variant children', function (ProductTypeEnum $type, bool $isParent, bool $isChild) {
        expect($type->isVariantParent())->toBe($isParent)
            ->and($type->isVariantChild())->toBe($isChild);
    })->with([
        [ProductTypeEnum::Configurable, true, false],
        [ProductTypeEnum::VariantGroup, true, true],
        [ProductTypeEnum::Simple, false, true],
    ]);

    it('lists the raw values of variant parents and variant children', function () {
        expect(ProductTypeEnum::VARIANT_PARENT_VALUES)->toBe(['configurable', 'variant_group'])
            ->and(ProductTypeEnum::VARIANT_CHILD_VALUES)->toBe(['variant_group', 'simple']);
    });

    it('maps every case in the variant level lookup used by the import hot path', function (ProductTypeEnum $type) {
        expect(ProductTypeEnum::VARIANT_LEVEL_BY_TYPE)->toHaveKey($type->value)
            ->and(ProductTypeEnum::VARIANT_LEVEL_BY_TYPE[$type->value])->toBe($type->variantLevel()->value);
    })->with(ProductTypeEnum::cases());

    it('resolves a translated label', function (ProductTypeEnum $type) {
        expect($type->label())->toBe(trans(config('product_types.'.$type->value.'.name')))
            ->and($type->label())->not->toStartWith('product::');
    })->with(ProductTypeEnum::cases());

    it('returns null for a type it does not know, such as a plugin type', function () {
        expect(ProductTypeEnum::tryFrom('bundle'))->toBeNull();
    });

    it('keeps the deprecated importer constants equal to the enum values', function () {
        expect(Importer::PRODUCT_TYPE_SIMPLE)->toBe(ProductTypeEnum::Simple->value)
            ->and(Importer::PRODUCT_TYPE_CONFIGURABLE)->toBe(ProductTypeEnum::Configurable->value)
            ->and(Importer::PRODUCT_TYPE_VARIANT_GROUP)->toBe(ProductTypeEnum::VariantGroup->value);
    });
});
