<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * @return array{0: Product, 1: string, 2: string}
 */
function makeTwoLevelStructureWithAxes(): array
{
    $family = AttributeFamily::factory()->create();

    $colorCode = 'guard_color_'.Str::random(8);
    $sizeCode = 'guard_size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'guard_structure_'.Str::random(8),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $configurable = Product::factory()->create([
        'sku'                  => 'guard-config-'.Str::random(8),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    return [$configurable, $colorCode, $sizeCode];
}

it('rejects a change to an ancestor-owned attribute', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-a']);
    $group->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-a']];
    $group->save();

    $simple = Product::factory()->create(['parent_id' => $group->id, 'type' => 'simple', 'sku' => 'grp-a-s']);
    $simple->values = ['common' => [$sizeCode => 's', 'sku' => 'grp-a-s']];
    $simple->save();

    $repository = app(ProductRepository::class);

    expect(fn () => $repository->guardVariantLevelWrite($simple->fresh(), [$colorCode => 'green']))
        ->toThrow(ValidationException::class);
});

it('allows a same-value resubmission of an ancestor-owned attribute', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-b']);
    $group->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-b']];
    $group->save();

    $simple = Product::factory()->create(['parent_id' => $group->id, 'type' => 'simple', 'sku' => 'grp-b-s']);
    $simple->values = ['common' => [$sizeCode => 's', 'sku' => 'grp-b-s']];
    $simple->save();

    $repository = app(ProductRepository::class);

    expect($repository->guardVariantLevelWrite($simple->fresh(), [$colorCode => 'red']))->toBe([$colorCode => 'red']);
});

it('allows an own-axis rename with no sibling collision', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-c']);
    $group->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-c']];
    $group->save();

    $repository = app(ProductRepository::class);

    expect($repository->guardVariantLevelWrite($group->fresh(), [$colorCode => 'green']))->toBe([$colorCode => 'green']);
});

it('rejects an own-axis rename that collides with a sibling', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $red = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-d-red']);
    $red->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-d-red']];
    $red->save();

    $pink = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-d-pink']);
    $pink->values = ['common' => [$colorCode => 'pink', 'sku' => 'grp-d-pink']];
    $pink->save();

    $repository = app(ProductRepository::class);

    expect(fn () => $repository->guardVariantLevelWrite($red->fresh(), [$colorCode => 'pink']))
        ->toThrow(ValidationException::class);
});

it('rejects a change to an axis attribute submitted directly on the root configurable product', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $repository = app(ProductRepository::class);

    expect(fn () => $repository->guardVariantLevelWrite($configurable->fresh(), [$colorCode => 'red']))
        ->toThrow(ValidationException::class);
});

it('detects a real violation on an array-valued ancestor-owned attribute instead of collapsing it to the string Array', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-e']);
    $group->values = ['common' => [$colorCode => ['red', 'blue'], 'sku' => 'grp-e']];
    $group->save();

    $simple = Product::factory()->create(['parent_id' => $group->id, 'type' => 'simple', 'sku' => 'grp-e-s']);
    $simple->values = ['common' => [$sizeCode => 's', 'sku' => 'grp-e-s']];
    $simple->save();

    $repository = app(ProductRepository::class);

    expect(fn () => $repository->guardVariantLevelWrite($simple->fresh(), [$colorCode => ['green', 'blue']]))
        ->toThrow(ValidationException::class);
});

it('allows a same-value resubmission of an array-valued ancestor-owned attribute', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-f']);
    $group->values = ['common' => [$colorCode => ['red', 'blue'], 'sku' => 'grp-f']];
    $group->save();

    $simple = Product::factory()->create(['parent_id' => $group->id, 'type' => 'simple', 'sku' => 'grp-f-s']);
    $simple->values = ['common' => [$sizeCode => 's', 'sku' => 'grp-f-s']];
    $simple->save();

    $repository = app(ProductRepository::class);

    expect($repository->guardVariantLevelWrite($simple->fresh(), [$colorCode => ['red', 'blue']]))
        ->toBe([$colorCode => ['red', 'blue']]);
});

it('runs the persist closure inside the guarded transaction after a successful own-axis rename', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-g']);
    $group->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-g']];
    $group->save();

    $repository = app(ProductRepository::class);

    $persisted = false;

    $result = $repository->guardVariantLevelWrite($group->fresh(), [$colorCode => 'green'], function () use (&$persisted): void {
        $persisted = true;
    });

    expect($result)->toBe([$colorCode => 'green'])
        ->and($persisted)->toBeTrue();
});

it('does not run the persist closure when an own-axis rename collides with a sibling', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $red = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-h-red']);
    $red->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-h-red']];
    $red->save();

    $pink = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-h-pink']);
    $pink->values = ['common' => [$colorCode => 'pink', 'sku' => 'grp-h-pink']];
    $pink->save();

    $repository = app(ProductRepository::class);

    $persisted = false;

    expect(fn () => $repository->guardVariantLevelWrite($red->fresh(), [$colorCode => 'pink'], function () use (&$persisted): void {
        $persisted = true;
    }))->toThrow(ValidationException::class);

    expect($persisted)->toBeFalse();
});

it('runs the persist closure directly, without the collision-check transaction, when an own-axis value is resubmitted unchanged', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelStructureWithAxes();

    $group = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'grp-i']);
    $group->values = ['common' => [$colorCode => 'red', 'sku' => 'grp-i']];
    $group->save();

    $repository = app(ProductRepository::class);

    $persisted = false;

    $result = $repository->guardVariantLevelWrite($group->fresh(), [$colorCode => 'red'], function () use (&$persisted): void {
        $persisted = true;
    });

    expect($result)->toBe([$colorCode => 'red'])
        ->and($persisted)->toBeTrue();
});
