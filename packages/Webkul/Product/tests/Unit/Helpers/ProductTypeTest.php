<?php

use Webkul\Product\Enums\ProductType;
use Webkul\Product\Helpers\ProductType as ProductTypeHelper;

describe('isProductType', function () {
    it('returns true for simple product type', function () {
        expect(ProductTypeHelper::isProductType(ProductType::Simple->value))->toBeTrue();
    });

    it('returns true for configurable product type', function () {
        expect(ProductTypeHelper::isProductType(ProductType::Configurable->value))->toBeTrue();
    });

    it('returns false for a nonexistent product type', function () {
        expect(ProductTypeHelper::isProductType('nonexistent'))->toBeFalse();
    });

    it('returns false for an empty string product type', function () {
        expect(ProductTypeHelper::isProductType(''))->toBeFalse();
    });
});

describe('hasVariants', function () {
    it('returns false for simple product type', function () {
        expect(ProductTypeHelper::hasVariants(ProductType::Simple->value))->toBeFalse();
    });

    it('returns true for configurable product type', function () {
        expect(ProductTypeHelper::hasVariants(ProductType::Configurable->value))->toBeTrue();
    });
});

describe('getAllTypesHavingVariants', function () {
    it('returns an array containing configurable', function () {
        $types = ProductTypeHelper::getAllTypesHavingVariants();

        expect($types)->toBeArray()
            ->and($types)->toContain(ProductType::Configurable->value);
    });

    it('does not include simple in the variant types', function () {
        $types = ProductTypeHelper::getAllTypesHavingVariants();

        expect($types)->not->toContain(ProductType::Simple->value);
    });

    it('returns a non-empty array', function () {
        $types = ProductTypeHelper::getAllTypesHavingVariants();

        expect($types)->not->toBeEmpty();
    });
});
