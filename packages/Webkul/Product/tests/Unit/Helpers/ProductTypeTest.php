<?php

use Webkul\Product\Helpers\ProductType;

describe('isProductType', function () {
    it('returns true for simple product type', function () {
        expect(ProductType::isProductType('simple'))->toBeTrue();
    });

    it('returns true for configurable product type', function () {
        expect(ProductType::isProductType('configurable'))->toBeTrue();
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
        expect(ProductType::hasVariants('simple'))->toBeFalse();
    });

    it('returns true for configurable product type', function () {
        expect(ProductType::hasVariants('configurable'))->toBeTrue();
    });
});

describe('getAllTypesHavingVariants', function () {
    it('returns an array containing configurable', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->toBeArray()
            ->and($types)->toContain('configurable');
    });

    it('does not include simple in the variant types', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->not->toContain('simple');
    });

    it('returns a non-empty array', function () {
        $types = ProductType::getAllTypesHavingVariants();

        expect($types)->not->toBeEmpty();
    });
});
