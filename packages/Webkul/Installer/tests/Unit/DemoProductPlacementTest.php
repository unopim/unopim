<?php

use Webkul\Admin\Http\Controllers\Catalog\AttributeFamilyController;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;
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
            expect($map[$code] ?? null)->toBe('variant');
        }
    });

    it('leaves axes at the level their own row fixes them to', function () {
        $single = placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes());

        expect($single['color'])->toBe('variant');

        $double = placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes());

        expect($double['color'])->toBe('sub_parent')
            ->and($double['size'])->toBe('variant');
    });

    it('never places an axis as a unique attribute', function () {
        $map = placementSeeder()->placementMap(['axes' => ['sku']], ['sku']);

        expect($map['sku'])->toBe('variant');
    });

    it('puts price on the variant so each sellable row carries its own', function () {
        expect(placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes())['price'])->toBe('variant');
    });

    it('keeps the main image common so every row below inherits one', function () {
        $single = placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes());
        $double = placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes());

        expect($single['image'])->toBe('common')
            ->and($double['image'])->toBe('common');
    });

    it('decides the gallery level from the depth of the structure', function () {
        expect(placementSeeder()->placementMap(['axes' => ['color']], demoUniqueCodes())['gallery'])->toBe('variant')
            ->and(placementSeeder()->placementMap(['axes' => ['color', 'size']], demoUniqueCodes())['gallery'])->toBe('sub_parent');
    });
});

describe('demo value ownership', function () {
    $placements = ['url_key' => 'variant', 'price' => 'variant', 'gallery' => 'sub_parent', 'image' => 'common'];

    it('keeps only the values placed at the given level', function () use ($placements) {
        $values = ['sku' => 'a', 'url_key' => 'a', 'price' => '10', 'gallery' => ['x'], 'image' => 'y', 'brand' => 'z'];

        expect(placementSeeder()->ownedValues($values, $placements, 'common'))
            ->toBe(['sku' => 'a', 'image' => 'y', 'brand' => 'z']);
    });

    it('carries sku at every level', function () use ($placements) {
        foreach (['common', 'sub_parent', 'variant'] as $level) {
            expect(placementSeeder()->ownedValues(['sku' => 'a'], $placements, $level))->toBe(['sku' => 'a']);
        }
    });

    it('treats an unplaced attribute as common', function () use ($placements) {
        expect(placementSeeder()->ownedValues(['brand' => 'z'], $placements, 'variant'))->toBe([]);
    });

    it('leaves values untouched when the product has no structure', function () {
        $values = ['sku' => 'a', 'url_key' => 'a', 'brand' => 'z'];

        expect(placementSeeder()->ownedValues($values, [], 'common'))->toBe($values);
    });
});
