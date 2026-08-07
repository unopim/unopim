<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('rejects a REST PUT that changes an ancestor-owned attribute value', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'rest_color_'.uniqid(), 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => 'rest_material_'.uniqid(), 'type' => 'text']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'rest_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'rest-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'rest-simple']);
    $simple->values = ['common' => ['sku' => 'rest-simple', $color->code => 'red']];
    $simple->save();

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', 'rest-simple'), [
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'rest-simple', $material->code => 'wood']],
    ])->assertStatus(422);
});

it('allows and persists an own-axis rename through the REST PUT path', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'rest2_color_'.uniqid(), 'type' => 'text']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'rest2_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'rest2-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'rest2-simple']);
    $simple->values = ['common' => ['sku' => 'rest2-simple', $color->code => 'red']];
    $simple->save();

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', 'rest2-simple'), [
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'rest2-simple', $color->code => 'green']],
    ])->assertOK();

    expect($simple->fresh()->values['common'][$color->code])->toBe('green');
});

it('rejects a REST PATCH that changes an ancestor-owned attribute value', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'rest3_color_'.uniqid(), 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => 'rest3_material_'.uniqid(), 'type' => 'text']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'rest3_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'rest3-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'rest3-simple']);
    $simple->values = ['common' => ['sku' => 'rest3-simple', $color->code => 'red']];
    $simple->save();

    $this->withHeaders($this->headers)->json('PATCH', route('admin.api.products.patch', ['sku' => 'rest3-simple']), [
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'rest3-simple', $material->code => 'wood']],
    ])->assertStatus(422);
});

it('allows and persists an own-axis rename through the REST PATCH path', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'rest4_color_'.uniqid(), 'type' => 'text']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'rest4_structure_'.uniqid(),
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'rest4-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $simple = Product::factory()->create(['parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'rest4-simple']);
    $simple->values = ['common' => ['sku' => 'rest4-simple', $color->code => 'red']];
    $simple->save();

    $this->withHeaders($this->headers)->json('PATCH', route('admin.api.products.patch', ['sku' => 'rest4-simple']), [
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'rest4-simple', $color->code => 'green']],
    ])->assertOK();

    expect($simple->fresh()->values['common'][$color->code])->toBe('green');
});
