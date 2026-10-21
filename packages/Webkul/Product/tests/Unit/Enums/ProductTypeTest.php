<?php

use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Enums\VariantLevel;

describe('ProductType', function () {
    it('keeps the legacy string values stored in products.type', function () {
        expect(ProductType::Simple->value)->toBe('simple')
            ->and(ProductType::Configurable->value)->toBe('configurable')
            ->and(ProductType::VariantGroup->value)->toBe('variant_group');
    });

    it('registers every case in the product_types config under its own value', function (ProductType $type) {
        expect(config('product_types'))->toHaveKey($type->value)
            ->and(config('product_types.'.$type->value.'.key'))->toBe($type->value);
    })->with(ProductType::cases());

    it('maps each type to the variant level it sits at', function (ProductType $type, VariantLevel $level) {
        expect($type->variantLevel())->toBe($level);
    })->with([
        [ProductType::Configurable, VariantLevel::Common],
        [ProductType::VariantGroup, VariantLevel::SubParent],
        [ProductType::Simple, VariantLevel::Variant],
    ]);

    it('lists the raw values of variant parents and variant children', function () {
        expect(ProductType::VARIANT_PARENT_VALUES)->toBe(['configurable', 'variant_group'])
            ->and(ProductType::VARIANT_CHILD_VALUES)->toBe(['variant_group', 'simple']);
    });

    it('maps every case in the variant level lookup used by the import hot path', function (ProductType $type) {
        expect(ProductType::VARIANT_LEVEL_BY_TYPE)->toHaveKey($type->value)
            ->and(ProductType::VARIANT_LEVEL_BY_TYPE[$type->value])->toBe($type->variantLevel()->value);
    })->with(ProductType::cases());

    it('returns null for a type it does not know, such as a plugin type', function () {
        expect(ProductType::tryFrom('bundle'))->toBeNull();
    });

    it('keeps the deprecated importer constants equal to the enum values', function () {
        expect(Importer::PRODUCT_TYPE_SIMPLE)->toBe(ProductType::Simple->value)
            ->and(Importer::PRODUCT_TYPE_CONFIGURABLE)->toBe(ProductType::Configurable->value)
            ->and(Importer::PRODUCT_TYPE_VARIANT_GROUP)->toBe(ProductType::VariantGroup->value);
    });
});
