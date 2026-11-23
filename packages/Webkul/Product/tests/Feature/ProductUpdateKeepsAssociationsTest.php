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

it('keeps links stored only in the link table when the update carries no associations', function (): void {
    $repository = app(ProductRepository::class);

    $source = Product::factory()->withInitialValues()->create(['type' => ProductType::Simple->value]);
    $related = Product::factory()->create();
    $upSell = Product::factory()->create();

    $repository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
        'up_sells'         => [$upSell->sku],
    ], $source->id);

    $values = $source->fresh()->values;
    unset($values['associations']);
    DB::table('products')->where('id', $source->id)->update(['values' => json_encode($values)]);

    $repository->update([
        'sku'    => $source->sku,
        'values' => ['common' => ['sku' => $source->sku]],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(2);
});

it('syncs only the legacy sections the update carries', function (): void {
    $repository = app(ProductRepository::class);

    $source = Product::factory()->withInitialValues()->create(['type' => ProductType::Simple->value]);
    $related = Product::factory()->create();
    $upSell = Product::factory()->create();
    $crossSell = Product::factory()->create();

    $repository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
        'up_sells'         => [$upSell->sku],
    ], $source->id);

    $values = $source->fresh()->values;
    unset($values['associations']);
    DB::table('products')->where('id', $source->id)->update(['values' => json_encode($values)]);

    $repository->update([
        'sku'         => $source->sku,
        'cross_sells' => [$crossSell->sku],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(3);
});

it('clears a legacy section when the values payload sends it empty', function (): void {
    $repository = app(ProductRepository::class);

    $source = Product::factory()->withInitialValues()->create(['type' => ProductType::Simple->value]);
    $related = Product::factory()->create();

    $repository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
    ], $source->id);

    $repository->update([
        'sku'    => $source->sku,
        'values' => [
            'common'       => ['sku' => $source->sku],
            'associations' => ['related_products' => []],
        ],
    ], $source->id);

    expect(DB::table('product_associations')->where('product_id', $source->id)->count())->toBe(0);
});
