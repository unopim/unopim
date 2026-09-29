<?php

use Webkul\Admin\Http\Controllers\Catalog\AttributeFamilyController;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;
use Webkul\Product\Enums\VariantLevelEnum;
use Webkul\Product\Repositories\ProductRepository;

/**
 * The demo catalog has to model variant structures the way the family form
 * does: unique attributes are forced onto the variant level, and a row may
 * only carry values for attributes placed at its own level, sku excepted.
 *
 * @see AttributeFamilyController::forceUniqueAttributesToVariant()
 * @see ProductRepository
 */
function placementSeeder(): DemoProductSeeder
{
    return resolve(DemoProductSeeder::class);
}

/** Unique attribute codes the demo families carry. */
function demoUniqueCodes(): array
{
    return ['sku', 'url_key', 'ean', 'product_number'];
}

describe('demo variant structure placements', function () {
    it('forces every unique attribute onto the variant level', function () {
        $map = placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes());

        foreach (['url_key', 'ean', 'product_number'] as $code) {
            expect($map[$code] ?? null)->toBe(VariantLevelEnum::Variant->value);
        }
    });

    it('leaves axes at the level their own row fixes them to', function () {
        $single = placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes());

        expect($single['color'])->toBe(VariantLevelEnum::Variant->value);

        $double = placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes());

        expect($double['color'])->toBe(VariantLevelEnum::SubParent->value)
            ->and($double['size'])->toBe(VariantLevelEnum::Variant->value);
    });

    it('never places an axis as a unique attribute', function () {
        $map = placementSeeder()->placementMap(['axes' => ['sku']], ['sku']);

        expect($map['sku'])->toBe(VariantLevelEnum::Variant->value);
    });

    it('puts price on the variant so each sellable row carries its own', function () {
        expect(placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes())['price'])->toBe(VariantLevelEnum::Variant->value);
    });

    it('keeps the main image common so every row below inherits one', function () {
        $single = placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes());
        $double = placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes());

        expect($single['image'])->toBe(VariantLevelEnum::Common->value)
            ->and($double['image'])->toBe(VariantLevelEnum::Common->value);
    });

    it('decides the gallery level from the depth of the structure', function () {
        expect(placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes())['gallery'])->toBe(VariantLevelEnum::Variant->value)
            ->and(placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes())['gallery'])->toBe(VariantLevelEnum::SubParent->value);
    });
});

describe('demo value ownership', function () {
    $placements = ['url_key' => VariantLevelEnum::Variant->value, 'price' => VariantLevelEnum::Variant->value, 'gallery' => VariantLevelEnum::SubParent->value, 'image' => VariantLevelEnum::Common->value];

    it('keeps only the values placed at the given level', function () use ($placements) {
        $values = ['sku' => 'a', 'url_key' => 'a', 'price' => '10', 'gallery' => ['x'], 'image' => 'y', 'brand' => 'z'];

        expect(placementSeeder()->ownedValues($values, $placements, VariantLevelEnum::Common->value))
            ->toBe(['sku' => 'a', 'image' => 'y', 'brand' => 'z']);
    });

    it('carries sku at every level', function () use ($placements) {
        foreach (VariantLevelEnum::VALUES as $level) {
            expect(placementSeeder()->ownedValues(['sku' => 'a'], $placements, $level))->toBe(['sku' => 'a']);
        }
    });

    it('treats an unplaced attribute as common', function () use ($placements) {
        expect(placementSeeder()->ownedValues(['brand' => 'z'], $placements, VariantLevelEnum::Variant->value))->toBe([]);
    });

    it('leaves values untouched when the product has no structure', function () {
        $values = ['sku' => 'a', 'url_key' => 'a', 'brand' => 'z'];

        expect(placementSeeder()->ownedValues($values, [], VariantLevelEnum::Common->value))->toBe($values);
    });
});
