<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('rejects a change to a non-axis attribute the simple does not own at its own level through AbstractType::update()', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'ag_color_'.uniqid(), 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => 'ag_material_'.uniqid(), 'type' => 'text']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'ag_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku'                  => 'ag-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'ag-simple']);
    $simple->values = ['common' => ['sku' => 'ag-simple']];
    $simple->save();

    expect(fn () => $repository->update([
        'sku'    => 'ag-simple',
        'values' => ['common' => [$material->code => 'wood']],
    ], $simple->id))->toThrow(ValidationException::class);
});

it('allows and persists an own-axis rename through AbstractType::update()', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'ag2_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'ag2_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku'                  => 'ag2-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'ag2-simple']);
    $simple->values = ['common' => [$color->code => 'red', 'sku' => 'ag2-simple']];
    $simple->save();

    $repository->update([
        'sku'    => 'ag2-simple',
        'values' => ['common' => [$color->code => 'green', 'sku' => 'ag2-simple']],
    ], $simple->id);

    expect($simple->fresh()->values['common'][$color->code])->toBe('green');
});

it("does not guard a root configurable's own update() call via AbstractType::update() (parent_id gate)", function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'ag3_color_'.uniqid(), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'ag3_size_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'ag3_structure_'.uniqid(),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku'                  => 'ag3-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    expect(fn () => $repository->guardVariantLevelWrite($configurable->fresh(), [$color->code => 'red']))
        ->toThrow(ValidationException::class);

    $repository->update([
        'sku'    => $configurable->sku,
        'values' => ['common' => [$color->code => 'red']],
    ], $configurable->id);

    expect($configurable->fresh()->values['common'][$color->code])->toBe('red');
});
