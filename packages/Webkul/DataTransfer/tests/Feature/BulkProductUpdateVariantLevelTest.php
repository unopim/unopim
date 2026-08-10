<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Jobs\System\BulkProductUpdate;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Bulk edit must apply the same variant-level rules as the UI and the API: a node may
 * change what it owns at its own level, subject to the sibling-scoped duplicate check,
 * and may not change what an ancestor owns. The fixture mirrors the `color_brand_size`
 * structure; codes are randomised because this suite runs against a seeded database.
 */
function bulkVariantLevelFixture(): array
{
    $suffix = Str::random(8);

    $colorCode = 'bvcolor_'.$suffix;
    $brandCode = 'bvbrand_'.$suffix;
    $sizeCode = 'bvsize_'.$suffix;
    $groupNoteCode = 'bvgnote_'.$suffix;
    $rootNoteCode = 'bvrnote_'.$suffix;

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $brand = Attribute::factory()->create(['code' => $brandCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $groupNote = Attribute::factory()->create([
        'code'              => $groupNoteCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $rootNote = Attribute::factory()->create([
        'code'              => $rootNoteCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create(['code' => 'bvfam_'.$suffix]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $colorCode,
        $brandCode,
        $sizeCode,
        $groupNoteCode,
        $rootNoteCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bvst_'.$suffix,
        'name'                => 'Bulk Variant Structure',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $brand->id, 'level' => 'level_1', 'position' => 1],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $groupNote->id, 'level' => 'sub_parent'],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $rootNote->id, 'level' => 'common'],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'BV-'.$suffix,
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $brandCode, $sizeCode],
    ]);

    $configurable->values = [
        'common' => [
            'sku'          => $configurable->sku,
            $rootNoteCode  => 'ROOT-OWNED',
        ],
    ];
    $configurable->save();

    $type = $configurable->getTypeInstance();

    $options = [
        'color' => $color->options->pluck('code')->all(),
        'brand' => $brand->options->pluck('code')->all(),
        'size'  => $size->options->pluck('code')->all(),
    ];

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $options['color'][0],
        'group_values'      => [$brandCode => $options['brand'][0]],
        'sku'               => $configurable->sku.'-G1',
    ]);

    $sibling = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $options['color'][1],
        'group_values'      => [$brandCode => $options['brand'][0]],
        'sku'               => $configurable->sku.'-G2',
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-G1-V1',
        'values'    => ['common' => [$sizeCode => $options['size'][0]]],
    ]);

    return [
        'configurable'  => $configurable->fresh(),
        'group'         => $group->fresh(),
        'sibling'       => $sibling->fresh(),
        'leaf'          => $leaf->fresh(),
        'colorCode'     => $colorCode,
        'brandCode'     => $brandCode,
        'sizeCode'      => $sizeCode,
        'groupNoteCode' => $groupNoteCode,
        'rootNoteCode'  => $rootNoteCode,
        'options'       => $options,
    ];
}

function runBulkVariantLevelUpdate(array $payload): JobTrack
{
    (new BulkProductUpdate($payload, auth()->guard('admin')->id()))->handle();

    return JobTrack::latest('id')->first();
}

function bulkVariantLevelOwnValue(Product $product, string $code): mixed
{
    return $product->fresh()->values['common'][$code] ?? null;
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('saves a variant group\'s own level_1 axis value through bulk edit', function () {
    $fixture = bulkVariantLevelFixture();

    $newColor = $fixture['options']['color'][2];

    runBulkVariantLevelUpdate([
        $fixture['group']->id => [$fixture['colorCode'] => $newColor],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['group'], $fixture['colorCode']))->toBe($newColor);
});

it('saves a leaf variant\'s own level_2 axis value through bulk edit', function () {
    $fixture = bulkVariantLevelFixture();

    $newSize = $fixture['options']['size'][1];

    runBulkVariantLevelUpdate([
        $fixture['leaf']->id => [$fixture['sizeCode'] => $newSize],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['leaf'], $fixture['sizeCode']))->toBe($newSize);
});

it('saves a non-axis attribute the variant group owns at its own level', function () {
    $fixture = bulkVariantLevelFixture();

    runBulkVariantLevelUpdate([
        $fixture['group']->id => [$fixture['groupNoteCode'] => 'GROUP-OWNED-NOTE'],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['group'], $fixture['groupNoteCode']))->toBe('GROUP-OWNED-NOTE');
});

it('refuses an ancestor-owned attribute and records it instead of writing it', function () {
    $fixture = bulkVariantLevelFixture();

    $jobTrack = runBulkVariantLevelUpdate([
        $fixture['group']->id => [$fixture['rootNoteCode'] => 'CHILD-OVERRIDE'],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['group'], $fixture['rootNoteCode']))->toBeNull()
        ->and(implode(' ', $jobTrack->errors ?? []))
        ->toContain($fixture['rootNoteCode'])
        ->toContain((string) $fixture['group']->id);
});

it('refuses an own-axis rename that collides with a sibling and leaves the sibling untouched', function () {
    $fixture = bulkVariantLevelFixture();

    $siblingColor = $fixture['options']['color'][1];

    $jobTrack = runBulkVariantLevelUpdate([
        $fixture['group']->id => [$fixture['colorCode'] => $siblingColor],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['group'], $fixture['colorCode']))->toBe($fixture['options']['color'][0])
        ->and(bulkVariantLevelOwnValue($fixture['sibling'], $fixture['colorCode']))->toBe($siblingColor)
        ->and(implode(' ', $jobTrack->errors ?? []))->toContain((string) $fixture['group']->id);
});

it('keeps processing the batch after one product is refused', function () {
    $fixture = bulkVariantLevelFixture();

    $newSize = $fixture['options']['size'][1];

    runBulkVariantLevelUpdate([
        $fixture['group']->id => [$fixture['colorCode'] => $fixture['options']['color'][1]],
        $fixture['leaf']->id  => [$fixture['sizeCode'] => $newSize],
    ]);

    expect(bulkVariantLevelOwnValue($fixture['group'], $fixture['colorCode']))->toBe($fixture['options']['color'][0])
        ->and(bulkVariantLevelOwnValue($fixture['leaf'], $fixture['sizeCode']))->toBe($newSize);
});
