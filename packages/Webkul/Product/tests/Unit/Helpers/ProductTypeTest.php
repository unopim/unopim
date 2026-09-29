<?php

use Webkul\Product\Enums\ProductTypeEnum;
use Webkul\Product\Helpers\ProductType;

describe('isProductType', function () {
    it('returns true for simple product type', function () {
        expect(ProductType::isProductType(ProductTypeEnum::Simple->value))->toBeTrue();
    });

    it('returns true for configurable product type', function () {
        expect(ProductType::isProductType(ProductTypeEnum::Configurable->value))->toBeTrue();
    });

    it('returns false for a nonexistent product type', function () {
        expect(ProductType::isProductType('nonexistent'))->toBeFalse();
    });

    it('returns false for an empty string product type', function () {
        expect(ProductType::isProductType(''))->toBeFalse();
    });
});

describe('hasVariants', function () {
    it('returns false for simple product type', function () {
        expect(ProductType::hasVariants(ProductTypeEnum::Simple->value))->toBeFalse();
    });

    it('returns true for configurable product type', function () {
        expect(ProductType::hasVariants(ProductTypeEnum::Configurable->value))->toBeTrue();
    });
});

describe('getAllTypesHavingVariants', function () {
    it('returns an array containing configurable', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->toBeArray()
            ->and($types)->toContain(ProductTypeEnum::Configurable->value);
    });

    it('does not include simple in the variant types', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->not->toContain(ProductTypeEnum::Simple->value);
    });

    it('returns a non-empty array', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->not->toBeEmpty();
    });
});
