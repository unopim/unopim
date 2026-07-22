<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Builds a fresh 2-level (color/size) variant structure + configurable product
 * with globally-unique, randomly-suffixed attribute codes (this suite runs
 * against a live/seeded DB where plain codes like "color" already exist).
 */
function makeTwoLevelConfigurable(): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

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

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    return [$configurable, $colorCode, $sizeCode, $family];
}

it('creates groups with nested variants on update for a 2-level product', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelConfigurable();

    app(ProductRepository::class)->update([
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

    $group = $configurable->refresh()->variants()->where('type', 'variant_group')->first();

    expect($group)->not->toBeNull()
        ->and($group->values['common'][$colorCode])->toBe('red')
        ->and($group->variants()->where('type', 'simple')->count())->toBe(1);

    $variant = $group->variants()->where('type', 'simple')->first();

    expect($variant->values['common'][$sizeCode])->toBe('s');
});

it('updates an existing group\'s values and its nested variant on update', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelConfigurable();

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

    $repository->update([
        'sku'            => $configurable->sku,
        'channel'        => 'default',
        'locale'         => 'en_US',
        'variant_groups' => [
            $group->id => [
                'group_values' => ['sku' => $group->sku],
                'variants'     => [
                    $variant->id => [
                        'sku'    => $variant->sku,
                        'values' => ['common' => [$sizeCode => 'm']],
                    ],
                ],
            ],
        ],
    ], $configurable->id);

    $group->refresh();
    $variant->refresh();

    expect($group->values['common'][$colorCode])->toBe('red')
        ->and($variant->values['common'][$sizeCode])->toBe('m')
        ->and($configurable->variants()->where('type', 'variant_group')->count())->toBe(1)
        ->and($group->variants()->where('type', 'simple')->count())->toBe(1);
});

it('prunes an orphaned group and its children when the group is dropped from the payload', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelConfigurable();

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

    $oldGroup = $configurable->variants()->where('type', 'variant_group')->first();
    $oldVariant = $oldGroup->variants()->where('type', 'simple')->first();

    $repository->update([
        'sku'            => $configurable->sku,
        'channel'        => 'default',
        'locale'         => 'en_US',
        'variant_groups' => [
            'group_2' => [
                'group_axis_option' => 'blue',
                'group_values'      => [],
                'sku'               => $configurable->sku.'-BLUE',
                'variants'          => [
                    'variant_1' => [
                        'sku'    => $configurable->sku.'-BLUE-S',
                        'values' => ['common' => [$sizeCode => 's']],
                    ],
                ],
            ],
        ],
    ], $configurable->id);

    $configurable->refresh();

    expect($configurable->variants()->where('type', 'variant_group')->count())->toBe(1)
        ->and($configurable->variants()->where('type', 'variant_group')->first()->values['common'][$colorCode])->toBe('blue')
        ->and(app(ProductRepository::class)->find($oldGroup->id))->toBeNull()
        ->and(app(ProductRepository::class)->find($oldVariant->id))->toBeNull();
});

it('leaves the legacy flat variants update path untouched for a 1-level/legacy configurable', function () {
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

    app(ProductRepository::class)->update([
        'sku'      => $configurable->sku,
        'channel'  => 'default',
        'locale'   => 'en_US',
        'variants' => [
            'variant_1' => [
                'sku'    => $configurable->sku.'-RED-S',
                'values' => ['common' => [$colorCode => 'red', $sizeCode => 's']],
            ],
        ],
    ], $configurable->id);

    $configurable->refresh();

    expect($configurable->variants()->where('type', 'variant_group')->count())->toBe(0)
        ->and($configurable->variants()->where('type', 'simple')->count())->toBe(1);

    $variant = $configurable->variants()->where('type', 'simple')->first();

    app(ProductRepository::class)->update([
        'sku'      => $configurable->sku,
        'channel'  => 'default',
        'locale'   => 'en_US',
        'variants' => [],
    ], $configurable->id);

    expect(app(ProductRepository::class)->find($variant->id))->toBeNull();
});

it('updates a 2-level variant whose payload omits the ancestor L1 axis without throwing', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelConfigurable();

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

    expect(fn () => $type->updateVariant([
        'sku'              => $variant->sku,
        // $colorCode (the L1 axis, owned by the ancestor group) is
        // intentionally omitted here - the 2-level guard must skip it
        // instead of throwing.
        'values'           => ['common' => [$sizeCode => 'm']],
        'super_attributes' => $configurable->super_attributes,
    ], $variant->id))->not->toThrow(Throwable::class);

    expect($variant->refresh()->values['common'][$sizeCode])->toBe('m');
});

it('fails loudly when a legacy 1-level updateVariant call omits a required axis', function () {
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

    $variant = $type->createVariant($configurable, $configurable->super_attributes, [
        'sku'    => $configurable->sku.'-RED-S',
        'values' => ['common' => [$colorCode => 'red', $sizeCode => 's']],
    ]);

    expect(fn () => $type->updateVariant([
        'sku'    => $variant->sku,
        // $sizeCode is intentionally omitted - legacy/1-level updates must
        // not silently skip a missing axis.
        'values'           => ['common' => [$colorCode => 'blue']],
        'super_attributes' => $configurable->super_attributes,
    ], $variant->id))->toThrow(ErrorException::class);
});

it('excludes variant_group nodes when resolving the default variant', function () {
    [$configurable, $colorCode, $sizeCode] = makeTwoLevelConfigurable();

    app(ProductRepository::class)->update([
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

    $type = $configurable->getTypeInstance();
    $type->setDefaultVariantId($group->id);
    $configurable->save();

    expect($type->getDefaultVariant())->toBeNull();
});
