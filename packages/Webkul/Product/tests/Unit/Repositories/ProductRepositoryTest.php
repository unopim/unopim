<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
});

describe('create', function () {
    it('creates a simple product via repository', function () {
        $family = AttributeFamily::find(1)
            ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

        $data = [
            'sku'                 => 'REPO-SIMPLE-'.uniqid(),
            'type'                => 'simple',
            'attribute_family_id' => $family->id,
        ];

        $product = $this->productRepository->create($data);

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->sku)->toBe($data['sku'])
            ->and($product->type)->toBe('simple')
            ->and($product->attribute_family_id)->toBe($family->id);

        $this->assertDatabaseHas('products', [
            'sku'  => $data['sku'],
            'type' => 'simple',
        ]);
    });

    it('creates a configurable product via repository', function () {
        $family = AttributeFamily::find(1)
            ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

        $data = [
            'sku'                 => 'REPO-CONFIG-'.uniqid(),
            'type'                => 'configurable',
            'attribute_family_id' => $family->id,
        ];

        $product = $this->productRepository->create($data);

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->type)->toBe('configurable');

        $this->assertDatabaseHas('products', [
            'sku'  => $data['sku'],
            'type' => 'configurable',
        ]);
    });
});

describe('updateStatus', function () {
    it('toggles product status from active to inactive', function () {
        $product = Product::factory()->create(['status' => 1]);

        $updated = $this->productRepository->updateStatus(false, $product->id);

        expect($updated->status)->toBe(0);

        $this->assertDatabaseHas('products', [
            'id'     => $product->id,
            'status' => 0,
        ]);
    });

    it('toggles product status from inactive to active', function () {
        $product = Product::factory()->create(['status' => 0]);

        $updated = $this->productRepository->updateStatus(true, $product->id);

        expect($updated->status)->toBe(1);

        $this->assertDatabaseHas('products', [
            'id'     => $product->id,
            'status' => 1,
        ]);
    });

    it('throws exception when product not found', function () {
        $this->productRepository->updateStatus(true, 999999);
    })->throws(ModelNotFoundException::class);
});

describe('copy', function () {
    it('duplicates a simple product with a new SKU', function () {
        $product = Product::factory()->withInitialValues()->create([
            'type' => 'simple',
        ]);

        $copiedProduct = $this->productRepository->copy($product->id);

        expect($copiedProduct)->toBeInstanceOf(Product::class)
            ->and($copiedProduct->id)->not->toBe($product->id)
            ->and($copiedProduct->sku)->not->toBe($product->sku)
            ->and($copiedProduct->type)->toBe($product->type)
            ->and($copiedProduct->attribute_family_id)->toBe($product->attribute_family_id);

        $this->assertDatabaseHas('products', [
            'id' => $copiedProduct->id,
        ]);
    });

    it('throws exception when copying a variant product', function () {
        $parent = Product::factory()->configurable()->create();

        $variant = Product::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $this->productRepository->copy($variant->id);
    })->throws(Exception::class);

    it('throws exception when product not found', function () {
        $this->productRepository->copy(999999);
    })->throws(ModelNotFoundException::class);
});

describe('findOrFail', function () {
    it('returns a product when found by id', function () {
        $product = Product::factory()->create();

        $found = $this->productRepository->findOrFail($product->id);

        expect($found->id)->toBe($product->id)
            ->and($found->sku)->toBe($product->sku);
    });

    it('throws exception when product is not found', function () {
        $this->productRepository->findOrFail(999999);
    })->throws(ModelNotFoundException::class);
});

describe('isUniqueVariantForProduct', function () {
    it('returns true when no duplicate variant exists', function () {
        $parent = Product::factory()->configurable()->create();

        $result = $this->productRepository->isUniqueVariantForProduct(
            $parent->id,
            ['color' => 'red']
        );

        expect($result)->toBeTrue();
    });

    it('returns true when checking with a different variant id', function () {
        $parent = Product::factory()->configurable()->create();

        $variant = Product::factory()->create([
            'parent_id' => $parent->id,
            'values'    => [
                'common' => ['color' => 'blue'],
            ],
        ]);

        $result = $this->productRepository->isUniqueVariantForProduct(
            $parent->id,
            ['color' => 'blue'],
            null,
            $variant->id
        );

        expect($result)->toBeTrue();
    });

    it('returns false when a sibling variant has the same configurable attributes', function () {
        $parent = Product::factory()->configurable()->create();

        Product::factory()->create([
            'parent_id' => $parent->id,
            'values'    => [
                'common' => ['color' => 'red'],
            ],
        ]);

        $result = $this->productRepository->isUniqueVariantForProduct(
            $parent->id,
            ['color' => 'red']
        );

        expect($result)->toBeFalse();
    });

    it('ignores the same variant id when checking uniqueness', function () {
        $parent = Product::factory()->configurable()->create();

        $variant = Product::factory()->create([
            'parent_id' => $parent->id,
            'values'    => [
                'common' => ['color' => 'green'],
            ],
        ]);

        $result = $this->productRepository->isUniqueVariantForProduct(
            $parent->id,
            ['color' => 'green'],
            null,
            $variant->id
        );

        expect($result)->toBeTrue();
    });

    it('returns false when another variant under the same parent already uses the SKU', function () {
        $parent = Product::factory()->configurable()->create();

        Product::factory()->create([
            'parent_id' => $parent->id,
            'sku'       => 'duplicate-sku',
            'values'    => [
                'common' => ['color' => 'blue'],
            ],
        ]);

        $result = $this->productRepository->isUniqueVariantForProduct(
            $parent->id,
            ['color' => 'yellow'],
            'duplicate-sku'
        );

        expect($result)->toBeFalse();
    });
});

describe('updateWithValues', function () {
    it('updates the values payload on a simple product', function () {
        $product = Product::factory()->withInitialValues()->create([
            'type' => 'simple',
        ]);

        $newValues = [
            'sku'    => $product->sku,
            'values' => [
                'common' => [
                    'sku'  => $product->sku,
                    'name' => 'Updated Product Name',
                ],
            ],
        ];

        $updated = $this->productRepository->updateWithValues($newValues, $product->id);

        expect($updated)->toBeInstanceOf(Product::class)
            ->and($updated->id)->toBe($product->id)
            ->and($updated->values['common']['name'] ?? null)->toBe('Updated Product Name');
    });

    it('returns a refreshed product after update', function () {
        $product = Product::factory()->withInitialValues()->create([
            'type' => 'simple',
        ]);

        $updated = $this->productRepository->updateWithValues([
            'sku'    => $product->sku,
            'values' => [
                'common' => [
                    'sku' => $product->sku,
                ],
            ],
        ], $product->id);

        expect($updated->wasRecentlyCreated)->toBeFalse()
            ->and($updated->exists)->toBeTrue();
    });

    it('throws when the product does not exist', function () {
        $this->productRepository->updateWithValues([
            'sku'    => 'whatever',
            'values' => ['common' => ['sku' => 'whatever']],
        ], 999999);
    })->throws(ModelNotFoundException::class);
});
