<?php

use Webkul\Core\Rules\Code;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;

/**
 * Guards the demo datasets against drift: every code a product references has
 * to exist in the attribute, family, category and association datasets, or the
 * seeded catalog silently loses values.
 */
function demoDataset(string $name): array
{
    return require __DIR__.'/../../src/Database/Data/Demo/'.$name.'.php';
}

function demoCatalog(): array
{
    $products = [];

    foreach (['audio', 'apparel', 'home', 'outdoor', 'beauty', 'food', 'sports', 'furniture'] as $file) {
        $products = array_merge($products, demoDataset('products/'.$file));
    }

    return $products;
}

/**
 * Attribute codes the base installer seeds, which the demo builds on top of.
 */
function baseAttributeCodes(): array
{
    return [
        'sku', 'name', 'url_key', 'tax_category_id', 'image', 'short_description', 'description',
        'price', 'cost', 'meta_title', 'meta_keywords', 'meta_description', 'length', 'width',
        'height', 'weight', 'color', 'size', 'brand', 'product_number', 'manage_stock',
    ];
}

function demoAttributeCodes(): array
{
    return array_merge(
        baseAttributeCodes(),
        array_column(demoDataset('attributes')['attributes'], 'code'),
    );
}

describe('demo dataset integrity', function () {
    it('gives every product a unique sku and a family that exists', function () {
        $families = array_column(demoDataset('families')['families'], 'code');
        $catalog = demoCatalog();
        $skus = array_column($catalog, 'sku');

        expect(array_unique($skus))->toHaveCount(count($skus));

        foreach ($catalog as $product) {
            expect($families)->toContain($product['family']);
        }
    });

    it('only references attributes the demo actually creates', function () {
        $known = demoAttributeCodes();

        $localeCodes = ['name', 'short_description', 'description', 'highlights', 'care_instructions', 'ingredients', 'storage_instructions'];

        foreach (demoCatalog() as $product) {
            foreach (array_keys($product['common'] ?? []) as $code) {
                expect($known)->toContain($code);
            }

            foreach ($product['locales'] as $copy) {
                foreach (array_keys($copy) as $code) {
                    expect($localeCodes)->toContain($code);
                }
            }
        }
    });

    it('only references categories that exist in the tree', function () {
        $codes = array_column(demoDataset('categories')['tree'], 'code');

        foreach (demoCatalog() as $product) {
            foreach ($product['categories'] ?? [] as $category) {
                expect($codes)->toContain($category);
            }
        }
    });

    it('gives every configurable an axis set its variants agree with', function () {
        foreach (demoCatalog() as $product) {
            if ($product['type'] !== 'configurable') {
                continue;
            }

            expect($product['axes'] ?? [])->not->toBeEmpty()
                ->and($product['variants'] ?? [])->not->toBeEmpty()
                ->and(count($product['axes']))->toBeLessThanOrEqual(2);

            foreach ($product['variants'] as $variant) {
                expect(array_keys($variant['axis']))->toBe($product['axes']);
            }
        }
    });

    it('only uses attributes the product family actually carries', function () {
        $families = [];

        foreach (demoDataset('families')['families'] as $family) {
            $codes = [];

            foreach ($family['groups'] as $group) {
                $codes = array_merge($codes, $group);
            }

            $families[$family['code']] = $codes;
        }

        foreach (demoCatalog() as $product) {
            $carried = array_merge($families[$product['family']] ?? [], ['sku', 'url_key']);

            foreach ($product['axes'] ?? [] as $axis) {
                expect($carried)->toContain($axis);
            }

            foreach (array_keys($product['common'] ?? []) as $code) {
                expect($carried)->toContain($code);
            }
        }
    });

    it('exercises at least one two-level variant structure', function () {
        $twoLevel = array_filter(
            demoCatalog(),
            static fn (array $product): bool => count($product['axes'] ?? []) === 2,
        );

        expect($twoLevel)->not->toBeEmpty();
    });

    it('covers every attribute type UnoPim ships', function () {
        $types = array_unique(array_column(demoDataset('attributes')['attributes'], 'type'));

        // text, textarea, price, boolean, image and select come from the base installer.
        $expected = ['multiselect', 'datetime', 'date', 'gallery', 'file', 'checkbox', 'measurement', 'select', 'text', 'textarea', 'boolean'];

        foreach ($expected as $type) {
            expect($types)->toContain($type);
        }
    });

    it('links associations only between products that exist', function () {
        $skus = array_column(demoCatalog(), 'sku');
        $data = demoDataset('associations');
        $typeCodes = array_column($data['types'], 'code');

        foreach ($data['links'] as $sku => $byType) {
            expect($skus)->toContain($sku);

            foreach ($byType as $typeCode => $targets) {
                expect($typeCodes)->toContain($typeCode);

                foreach ($targets as $target) {
                    expect($skus)->toContain(is_array($target) ? $target['sku'] : $target);
                }
            }
        }
    });

    it('ships an image reference for every product the seeder can resolve', function () {
        $catalogPath = __DIR__.'/../../src/Resources/assets/images/demo/catalog';

        foreach (demoCatalog() as $product) {
            expect($product['media'] ?? null)->not->toBeNull()
                ->and(file_exists($catalogPath.'/'.$product['media'].'.webp'))->toBeTrue();
        }
    });

    it('loads the whole catalog through the product seeder', function () {
        expect(resolve(DemoProductSeeder::class)->catalog())->toHaveCount(count(demoCatalog()));
    });

    it('derives variant structure codes the family form accepts', function () {
        $seeder = resolve(DemoProductSeeder::class);
        $rule = new Code;
        $codes = [];

        foreach (demoCatalog() as $product) {
            if (($product['axes'] ?? []) === []) {
                continue;
            }

            $code = $seeder->structureCode($product['sku']);
            $codes[] = $code;

            $rule->validate('structure.code', $code, function (string $message) use ($product): void {
                throw new Exception("{$product['sku']} yields an invalid structure code: $message");
            });
        }

        expect($codes)->not->toBeEmpty()
            ->and(array_unique($codes))->toHaveCount(count($codes));
    });
});
