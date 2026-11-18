<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('keeps existing associations when a product is updated without an associations payload', function (): void {
    $repository = app(ProductRepository::class);

    $source = Product::factory()->withInitialValues()->create(['type' => ProductType::Simple->value]);
    $related = Product::factory()->create();

    $repository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(1);

    $repository->update([
        'sku'    => $source->sku,
        'values' => ['common' => ['sku' => $source->sku]],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(1)
        ->and($source->fresh()->values['associations']['related_products'] ?? null)->toBe([$related->sku]);
});

it('still clears the link when only the __present sentinel is sent for a type', function (): void {
    $repository = app(ProductRepository::class);

    $source = Product::factory()->withInitialValues()->create(['type' => ProductType::Simple->value]);
    $related = Product::factory()->create();

    $repository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(1);

    $repository->update([
        'sku'          => $source->sku,
        'values'       => ['common' => ['sku' => $source->sku]],
        'associations' => ['related_products' => ['__present' => 1]],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(0)
        ->and($source->fresh()->values['associations']['related_products'] ?? [])->toBe([]);
});
