<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Services\VariantValueResolver;

it('merges common values root-to-leaf with child overriding by key presence', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['brand' => 'Nike', 'material' => 'Cotton']];
    $group = ['common' => ['image' => 'red.jpg']];
    $leaf = ['common' => ['size' => 'S', 'sku' => 'TEE-RED-S']];

    $resolved = $resolver->mergeChain([$root, $group, $leaf]);

    expect($resolved['common'])->toMatchArray([
        'brand'    => 'Nike',
        'material' => 'Cotton',
        'image'    => 'red.jpg',
        'size'     => 'S',
        'sku'      => 'TEE-RED-S',
    ]);
});

it('lets a descendant override an ancestor key (override by presence)', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['price' => '10.00', 'brand' => 'Nike']];
    $leaf = ['common' => ['price' => '19.00']];

    $resolved = $resolver->mergeChain([$root, $leaf]);

    expect($resolved['common']['price'])->toBe('19.00')
        ->and($resolved['common']['brand'])->toBe('Nike');
});

it('preserves the leaf non-common scopes untouched', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['brand' => 'Nike']];
    $leaf = [
        'common'           => ['size' => 'S'],
        'channel_specific' => ['default' => ['seo_title' => 'Red S']],
    ];

    $resolved = $resolver->mergeChain([$root, $leaf]);

    expect($resolved['channel_specific'])->toBe(['default' => ['seo_title' => 'Red S']]);
});

it('binds the resolver contract to the implementation in the container', function () {
    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class);

    expect($resolved)->toBeInstanceOf(VariantValueResolver::class);
});

it('resolves a variant\'s values from its configurable parent', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['brand' => 'Nike', 'material' => 'Cotton']],
    ]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['size' => 'S', 'sku' => $parent->sku.'-S']],
    ]);

    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class)->resolve($variant);

    expect($resolved['common'])->toMatchArray([
        'brand'    => 'Nike',
        'material' => 'Cotton',
        'size'     => 'S',
    ]);
});

it('returns own values unchanged for a product with no parent', function () {
    $simple = Product::factory()->create([
        'values' => ['common' => ['sku' => 'STANDALONE', 'name' => 'Solo']],
    ]);

    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class)->resolve($simple);

    expect($resolved['common'])->toMatchArray(['sku' => 'STANDALONE', 'name' => 'Solo']);
});

it('resolveBatch resolves a 2-level chain the same way resolve() does', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['name' => 'Parent Name', 'brand' => 'Nike']],
    ]);

    $group = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['color' => 'red']],
    ]);

    $leaf = Product::factory()->create([
        'parent_id' => $group->id,
        'values'    => ['common' => ['size' => 'S', 'sku' => $parent->sku.'-red-S']],
    ]);

    $resolver = app(Webkul\Product\Contracts\VariantValueResolver::class);

    $batch = $resolver->resolveBatch([
        ['id' => $leaf->id, 'parent_id' => $leaf->parent_id, 'values' => $leaf->values],
        ['id' => $group->id, 'parent_id' => $group->parent_id, 'values' => $group->values],
    ]);

    expect($batch[$leaf->id]['common'])->toMatchArray([
        'name'  => 'Parent Name',
        'brand' => 'Nike',
        'color' => 'red',
        'size'  => 'S',
    ])->and($batch[$group->id]['common'])->toMatchArray([
        'name'  => 'Parent Name',
        'brand' => 'Nike',
        'color' => 'red',
    ]);
});

it('resolveBatch omits a row with no parent so the caller falls back to its own values', function () {
    $simple = Product::factory()->create([
        'values' => ['common' => ['sku' => 'STANDALONE', 'name' => 'Solo']],
    ]);

    $resolver = app(Webkul\Product\Contracts\VariantValueResolver::class);

    $batch = $resolver->resolveBatch([
        ['id' => $simple->id, 'parent_id' => null, 'values' => $simple->values],
    ]);

    expect($batch)->not->toHaveKey($simple->id);
});

it('resolveBatch omits a row whose own values are null and has no parent, preserving that null for the caller', function () {
    $simple = Product::factory()->create(['values' => null]);

    $resolver = app(Webkul\Product\Contracts\VariantValueResolver::class);

    $batch = $resolver->resolveBatch([
        ['id' => $simple->id, 'parent_id' => null, 'values' => null],
    ]);

    expect($batch)->not->toHaveKey($simple->id);
});

it('resolveBatch omits a row whose parent_id points at a deleted/orphaned product, preserving its own values for the caller', function () {
    $orphan = Product::factory()->create([
        'parent_id' => null,
        'values'    => ['common' => ['sku' => 'ORPHANED-CHILD', 'name' => 'Kept As-Is']],
    ]);

    $missingAncestorId = $orphan->id + 1_000_000;

    $resolver = app(Webkul\Product\Contracts\VariantValueResolver::class);

    $batch = $resolver->resolveBatch([
        ['id' => $orphan->id, 'parent_id' => $missingAncestorId, 'values' => $orphan->values],
    ]);

    expect($batch)->not->toHaveKey($orphan->id);
});

it('resolveBatch fetches a shared ancestor only once for many rows on the same page', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['name' => 'Shared Parent Name']],
    ]);

    $children = Product::factory()->count(5)->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => []],
    ]);

    DB::enableQueryLog();

    $resolver = app(Webkul\Product\Contracts\VariantValueResolver::class);

    $batch = $resolver->resolveBatch(
        $children->map(fn ($child) => ['id' => $child->id, 'parent_id' => $child->parent_id, 'values' => $child->values])
    );

    $ancestorLookups = collect(DB::getQueryLog())->filter(
        fn ($entry) => str_contains($entry['query'], 'products') && str_contains(strtolower($entry['query']), ' in (')
    );

    DB::disableQueryLog();

    foreach ($children as $child) {
        expect($batch[$child->id]['common']['name'])->toBe('Shared Parent Name');
    }

    expect($ancestorLookups->count())->toBe(1);
});
