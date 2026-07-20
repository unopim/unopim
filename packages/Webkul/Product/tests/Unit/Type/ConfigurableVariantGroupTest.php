<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('materializes configurable → variant_group → simple and resolves the chain', function () {
    // Codes are suffixed with a random string because this suite runs against a
    // seeded database that already has attributes named "color", "size", "swatch", etc.
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $swatchCode = 'swatch_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $swatch = Attribute::factory()->create(['code' => $swatchCode, 'type' => 'text']);

    $family = AttributeFamily::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);
    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $swatch->id, 'level' => 'sub_parent'],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);
    $configurable->values = ['common' => ['brand' => 'Acme', 'sku' => $configurable->sku]];
    $configurable->save();

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => 'red',
        'group_values'      => [$swatchCode => 'red.png'],
        'sku'               => $configurable->sku.'-RED',
    ]);

    expect($group->type)->toBe('variant_group')
        ->and($group->parent_id)->toBe($configurable->id)
        ->and($group->values['common'])->toMatchArray([$colorCode => 'red', $swatchCode => 'red.png']);

    $variant = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-RED-S',
        'values'    => ['common' => [$sizeCode => 's']],
    ]);

    expect($variant->parent_id)->toBe($group->id);

    $resolved = app(VariantValueResolver::class)->resolve($variant->refresh());

    expect($resolved['common'])->toMatchArray([
        'brand'     => 'Acme',
        $swatchCode => 'red.png',
        $sizeCode   => 's',
    ]);
});

it('fails loudly when a legacy 1-level createVariant call omits a required axis', function () {
    // Legacy / 1-level configurable: no variant_structure_id, no parent_id on
    // the variant payload. The 2-level "axis lives on the ancestor group" skip
    // must NOT apply here - a missing axis is a malformed payload and should
    // throw, not silently create a variant missing that axis value.
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create();

    $configurable = app(ProductRepository::class)->create([
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
        'sku'                 => 'TEE-'.Str::random(8),
        'super_attributes'    => [$colorCode, $sizeCode],
    ]);

    $type = $configurable->getTypeInstance();

    expect(fn () => $type->createVariant($configurable, $configurable->super_attributes, [
        'sku'    => $configurable->sku.'-RED',
        // $sizeCode is intentionally omitted - the payload only supplies one
        // of the two required axes.
        'values' => ['common' => [$colorCode => 'red']],
    ]))->toThrow(ErrorException::class);
});
