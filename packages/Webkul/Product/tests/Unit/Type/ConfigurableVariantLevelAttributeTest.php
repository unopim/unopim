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

/**
 * Builds a fresh 2-level (color/size) variant structure with an additional
 * `variant`-level, common-scope placement attribute, plus a configurable
 * product. Codes are randomly suffixed because this suite runs against a
 * live/seeded DB where plain codes like "color" already exist.
 */
function makeTwoLevelConfigurableWithVariantAttribute(): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $materialCode = 'material_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => $materialCode, 'type' => 'text']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

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
        ['variant_structure_id' => $structure->id, 'attribute_id' => $material->id, 'level' => 'variant'],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    return [$configurable, $colorCode, $sizeCode, $materialCode];
}

it('persists a variant-level common attribute from the payload on createVariant for a 2-level leaf', function () {
    [$configurable, $colorCode, $sizeCode, $materialCode] = makeTwoLevelConfigurableWithVariantAttribute();

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => 'red',
        'group_values'      => [],
        'sku'               => $configurable->sku.'-RED',
    ]);

    $variant = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-RED-S',
        'values'    => ['common' => [$sizeCode => 's', $materialCode => 'cotton']],
    ]);

    expect($variant->values['common'][$materialCode])->toBe('cotton')
        ->and($variant->values['common'])->not->toHaveKey($colorCode);

    $resolved = app(VariantValueResolver::class)->resolve($variant->refresh());

    expect($resolved['common'])->toMatchArray([
        $colorCode    => 'red',
        $sizeCode     => 's',
        $materialCode => 'cotton',
    ]);
});

it('leaves legacy/no-structure createVariant unaffected by the variant-level attribute write', function () {
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    $configurable = app(ProductRepository::class)->create([
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
        'sku'                 => 'TEE-'.Str::random(8),
        'super_attributes'    => [$colorCode, $sizeCode],
    ]);

    $type = $configurable->getTypeInstance();

    $variant = $type->createVariant($configurable, $configurable->super_attributes, [
        'sku'    => $configurable->sku.'-RED-S',
        'values' => ['common' => [$colorCode => 'red', $sizeCode => 's']],
    ]);

    expect($variant->values['common'])->toBe([
        $colorCode => 'red',
        $sizeCode  => 's',
        'sku'      => $variant->sku,
    ]);

    // Existing guard logic must remain untouched: a genuinely-missing axis
    // still fails loudly on a legacy/1-level configurable.
    expect(fn () => $type->createVariant($configurable, $configurable->super_attributes, [
        'sku'    => $configurable->sku.'-BLUE',
        'values' => ['common' => [$colorCode => 'blue']],
    ]))->toThrow(ErrorException::class);
});

it('persists a variant-level common attribute from the payload on updateVariant for an existing 2-level leaf', function () {
    [$configurable, $colorCode, $sizeCode, $materialCode] = makeTwoLevelConfigurableWithVariantAttribute();

    $repository = app(ProductRepository::class);

    $repository->update([
        'sku'            => $configurable->sku,
        'channel'        => 'default',
        'locale'         => 'en_US',
        'variant_groups' => [
            'group_1' => [
                'group_axis_option' => 'red',
                'group_values'      => [],
                'sku'               => $configurable->sku.'-RED',
                'variants'          => [
                    'variant_1' => [
                        'sku'    => $configurable->sku.'-RED-S',
                        'values' => ['common' => [$sizeCode => 's']],
                    ],
                ],
            ],
        ],
    ], $configurable->id);

    $configurable->refresh();

    $group = $configurable->variants()->where('type', 'variant_group')->first();
    $variant = $group->variants()->where('type', 'simple')->first();

    $type = $configurable->getTypeInstance();

    $type->updateVariant([
        'sku'              => $variant->sku,
        'values'           => ['common' => [$sizeCode => 's', $materialCode => 'linen']],
        'super_attributes' => $configurable->super_attributes,
    ], $variant->id);

    $variant->refresh();

    expect($variant->values['common'][$materialCode])->toBe('linen')
        ->and($variant->values['common'][$sizeCode])->toBe('s');

    $resolved = app(VariantValueResolver::class)->resolve($variant);

    expect($resolved['common'])->toMatchArray([
        $colorCode    => 'red',
        $sizeCode     => 's',
        $materialCode => 'linen',
    ]);
});
