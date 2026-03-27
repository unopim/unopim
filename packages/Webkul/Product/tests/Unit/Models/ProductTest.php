<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

describe('Product creation via factory', function () {
    it('creates a simple product with default state', function () {
        $product = Product::factory()->create();

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->type)->toBe('simple')
            ->and($product->status)->toBe(1)
            ->and($product->sku)->not->toBeEmpty()
            ->and($product->id)->toBeGreaterThan(0);

        $this->assertDatabaseHas('products', [
            'id'   => $product->id,
            'type' => 'simple',
            'sku'  => $product->sku,
        ]);
    });

    it('creates a simple product using the simple state', function () {
        $product = Product::factory()->simple()->create();

        expect($product->type)->toBe('simple');
    });

    it('creates a configurable product using the configurable state', function () {
        $product = Product::factory()->configurable()->create();

        expect($product->type)->toBe('configurable')
            ->and($product->id)->toBeGreaterThan(0);

        $this->assertDatabaseHas('products', [
            'id'   => $product->id,
            'type' => 'configurable',
        ]);
    });

    it('creates a product with initial values state', function () {
        $product = Product::factory()->withInitialValues()->create();

        expect($product->values)->toBeArray()
            ->and($product->values)->toHaveKey('common')
            ->and($product->values['common'])->toHaveKey('sku')
            ->and($product->values['common']['sku'])->toBe($product->sku);
    });

    it('generates unique SKUs for each factory product', function () {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        expect($product1->sku)->not->toBe($product2->sku);
    });
});

describe('Product relationships', function () {
    it('has an attribute_family relationship that returns BelongsTo', function () {
        $product = Product::factory()->create();

        expect($product->attribute_family())->toBeInstanceOf(BelongsTo::class);
    });

    it('loads the associated attribute family', function () {
        $product = Product::factory()->create();

        $family = $product->attribute_family;

        expect($family)->toBeInstanceOf(AttributeFamily::class)
            ->and($family->id)->toBe($product->attribute_family_id);
    });

    it('has a variants relationship that returns HasMany', function () {
        $product = Product::factory()->create();

        expect($product->variants())->toBeInstanceOf(HasMany::class);
    });

    it('has a parent relationship that returns BelongsTo', function () {
        $product = Product::factory()->create();

        expect($product->parent())->toBeInstanceOf(BelongsTo::class);
    });

    it('has a super_attributes relationship that returns BelongsToMany', function () {
        $product = Product::factory()->create();

        expect($product->super_attributes())->toBeInstanceOf(BelongsToMany::class);
    });

    it('has an images relationship method defined', function () {
        $product = Product::factory()->create();

        expect(method_exists($product, 'images'))->toBeTrue();
    });

    it('has a completenessScores relationship that returns HasMany', function () {
        $product = Product::factory()->create();

        expect($product->completenessScores())->toBeInstanceOf(HasMany::class);
    });

    it('returns null parent for a root product', function () {
        $product = Product::factory()->create();

        expect($product->parent)->toBeNull();
    });

    it('returns parent product for a variant', function () {
        $parent = Product::factory()->configurable()->create();
        $variant = Product::factory()->create([
            'parent_id' => $parent->id,
        ]);

        expect($variant->parent)->toBeInstanceOf(Product::class)
            ->and($variant->parent->id)->toBe($parent->id);
    });

    it('returns variants for a configurable product', function () {
        $parent = Product::factory()->configurable()->create();

        Product::factory()->count(3)->create([
            'parent_id' => $parent->id,
        ]);

        $parent->refresh();

        expect($parent->variants)->toHaveCount(3)
            ->and($parent->variants->first())->toBeInstanceOf(Product::class);
    });
});

describe('Product attribute casts', function () {
    it('casts values to array', function () {
        $product = Product::factory()->create([
            'values' => [
                'common' => ['sku' => 'TEST-CAST'],
            ],
        ]);

        $product->refresh();

        expect($product->values)->toBeArray()
            ->and($product->values['common']['sku'])->toBe('TEST-CAST');
    });

    it('casts additional to array', function () {
        $product = Product::factory()->create([
            'additional' => [
                'meta_title' => 'Test Title',
                'meta_desc'  => 'Test Description',
            ],
        ]);

        $product->refresh();

        expect($product->additional)->toBeArray()
            ->and($product->additional['meta_title'])->toBe('Test Title');
    });

    it('returns null for values when not set', function () {
        $product = Product::factory()->create();

        expect($product->getRawOriginal('values'))->toBeNull();
    });

    it('returns null for additional when not set', function () {
        $product = Product::factory()->create();

        expect($product->getRawOriginal('additional'))->toBeNull();
    });
});

describe('Product fillable attributes', function () {
    it('has the expected fillable attributes', function () {
        $product = new Product;

        expect($product->getFillable())->toBe([
            'type',
            'attribute_family_id',
            'sku',
            'parent_id',
            'status',
        ]);
    });
});
