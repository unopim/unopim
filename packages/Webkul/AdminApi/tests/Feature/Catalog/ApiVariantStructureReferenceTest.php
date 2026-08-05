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

function vsrTwoAxisTree(string $prefix): array
{
    $family = AttributeFamily::factory()->create();

    $color = Attribute::factory()->create(['code' => $prefix.'_color_'.uniqid(), 'type' => 'select']);
    $brand = Attribute::factory()->create(['code' => $prefix.'_brand_'.uniqid(), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $prefix.'_size_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => $prefix.'_structure_'.uniqid(),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $brand->id, 'level' => 'level_1', 'position' => 1],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('id', [$color->id, $brand->id, $size->id])->get());

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => $prefix.'-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    return [$family, $structure, $color, $brand, $size, $configurable];
}

it('creates two variant groups that differ only in the second level-1 axis', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr1');

    $colorOption = $color->options->first()->code;
    $firstBrand = $brand->options->first()->code;
    $secondBrand = $brand->options->last()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vsr1-group-a', $color->code => $colorOption, $brand->code => $firstBrand]],
    ])->assertStatus(201);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vsr1-group-b', $color->code => $colorOption, $brand->code => $secondBrand]],
    ])->assertStatus(201);

    $groups = Product::whereIn('sku', ['vsr1-group-a', 'vsr1-group-b'])->get();

    expect($groups)->toHaveCount(2);
    expect($groups->firstWhere('sku', 'vsr1-group-b')->values['common'][$brand->code])->toBe($secondBrand);
    expect($groups->firstWhere('sku', 'vsr1-group-b')->values['common'][$color->code])->toBe($colorOption);
});

it('rejects a variant group whose full level-1 axis tuple already exists', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr2');

    $colorOption = $color->options->first()->code;
    $brandOption = $brand->options->first()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vsr2-group-a', $color->code => $colorOption, $brand->code => $brandOption]],
    ])->assertStatus(201);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vsr2-group-b', $color->code => $colorOption, $brand->code => $brandOption]],
    ])->assertStatus(422);

    expect(Product::where('sku', 'vsr2-group-b')->exists())->toBeFalse();
});

it('rejects a variant group create that omits one of the level-1 axes', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr3');

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vsr3-group-a', $color->code => $color->options->first()->code]],
    ])->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain($brand->code);
    expect(Product::where('sku', 'vsr3-group-a')->exists())->toBeFalse();
});

it('sets variant_structure_id and derives super attributes from a referenced variant structure', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr4');

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $family->code,
        'variant_structure' => $structure->code,
        'values'            => ['common' => ['sku' => 'vsr4-config-new']],
    ])->assertStatus(201);

    $product = Product::where('sku', 'vsr4-config-new')->first();

    expect($product)->not->toBeNull();
    expect($product->variant_structure_id)->toBe($structure->id);
    expect($product->super_attributes->pluck('code')->sort()->values()->all())
        ->toBe(collect([$color->code, $brand->code, $size->code])->sort()->values()->all());
});

it('accepts super attributes that match the referenced variant structure axes', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr5');

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $family->code,
        'variant_structure' => $structure->code,
        'super_attributes'  => [$size->code, $color->code, $brand->code],
        'values'            => ['common' => ['sku' => 'vsr5-config-new']],
    ])->assertStatus(201);

    expect(Product::where('sku', 'vsr5-config-new')->first()->variant_structure_id)->toBe($structure->id);
});

it('rejects super attributes that disagree with the referenced variant structure axes', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr6');

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $family->code,
        'variant_structure' => $structure->code,
        'super_attributes'  => [$color->code],
        'values'            => ['common' => ['sku' => 'vsr6-config-new']],
    ])->assertStatus(422);

    expect(Product::where('sku', 'vsr6-config-new')->exists())->toBeFalse();
});

it('rejects an unknown variant structure code', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr7');

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $family->code,
        'variant_structure' => 'vsr7-does-not-exist',
        'values'            => ['common' => ['sku' => 'vsr7-config-new']],
    ])->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('vsr7-does-not-exist');
    expect(Product::where('sku', 'vsr7-config-new')->exists())->toBeFalse();
});

it('rejects a variant structure code that belongs to another family', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr8');

    $otherFamily = AttributeFamily::factory()->create();
    $otherAttribute = Attribute::factory()->create(['code' => 'vsr8_other_'.uniqid(), 'type' => 'select']);

    AttributeFamily::factory()->linkAttributeGroupToFamily($otherFamily);
    AttributeFamily::factory()->linkAttributesToFamily($otherFamily, $otherAttribute);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $otherFamily->code,
        'variant_structure' => $structure->code,
        'super_attributes'  => [$otherAttribute->code],
        'values'            => ['common' => ['sku' => 'vsr8-config-new']],
    ])->assertStatus(422);

    expect(Product::where('sku', 'vsr8-config-new')->exists())->toBeFalse();
});

it('rejects a variant structure sent on the configurable product PUT endpoint', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr9');

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configurable_products.update', $configurable->sku), [
        'family'            => $family->code,
        'variant_structure' => $structure->code,
        'values'            => ['common' => ['sku' => $configurable->sku]],
    ])->assertStatus(422);
});

it('rejects a variant structure sent on the configurable product PATCH endpoint', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr10');

    $this->withHeaders($this->headers)->json('PATCH', route('admin.api.configurable_products.patch', $configurable->sku), [
        'variant_structure' => $structure->code,
        'values'            => ['common' => ['sku' => $configurable->sku]],
    ])->assertStatus(422);
});

it('builds a full two-level variant tree through the REST API', function () {
    [$family, $structure, $color, $brand, $size, $configurable] = vsrTwoAxisTree('vsr11');

    $colorOption = $color->options->first()->code;
    $brandOption = $brand->options->first()->code;
    $sizeOption = $size->options->first()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'family'            => $family->code,
        'variant_structure' => $structure->code,
        'values'            => ['common' => ['sku' => 'vsr11-config']],
    ])->assertStatus(201);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => 'vsr11-config',
        'values' => ['common' => ['sku' => 'vsr11-group', $color->code => $colorOption, $brand->code => $brandOption]],
    ])->assertStatus(201);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => 'vsr11-group',
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vsr11-leaf']],
        'variant' => ['attributes' => [$size->code => $sizeOption]],
    ])->assertStatus(201);

    $root = Product::where('sku', 'vsr11-config')->first();
    $group = Product::where('sku', 'vsr11-group')->first();
    $leaf = Product::where('sku', 'vsr11-leaf')->first();

    expect($root->variant_structure_id)->toBe($structure->id);
    expect($group->parent_id)->toBe($root->id);
    expect($group->type)->toBe('variant_group');
    expect($leaf->parent_id)->toBe($group->id);
    expect($leaf->type)->toBe('simple');
    expect($leaf->values['common'][$size->code])->toBe($sizeOption);
});
