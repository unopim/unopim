<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\User\Models\Admin;

uses(DatabaseTransactions::class);

/**
 * Builds a fresh 2-level (color/size) configurable + variant structure,
 * globally-unique/randomly-suffixed since this suite runs against a
 * live/seeded DB (attributes.code is globally unique). Mirrors the helper in
 * ProductVariantNodeCreateTest.php, plus grows the `size` attribute's option
 * pool so pagination/search scenarios have enough distinct axis values.
 */
function makeConfigurableForVariantChildren(int $extraSizeOptions = 0): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    if ($extraSizeOptions > 0) {
        AttributeOption::factory()->count($extraSizeOptions)->create(['attribute_id' => $size->id]);
    }

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

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    return [$configurable, $color, $size, $colorCode, $sizeCode];
}

it('lists a configurable\'s direct variant_group children with axis label, sku and redirect_url', function () {
    [$configurable, $color, , $colorCode] = makeConfigurableForVariantChildren();

    $redOptionCode = $color->options->first()->code;

    $color->options->first()->translateOrNew('en_US')->label = 'Fire Red';
    $color->options->first()->save();

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'axis' => $colorCode,
        ]))
        ->assertOk();

    $options = $response->json('options');

    expect($options)->toHaveCount(1)
        ->and($options[0]['id'])->toBe($group->id)
        ->and($options[0]['axisValues'])->toBe([$colorCode => 'Fire Red'])
        ->and($options[0]['label'])->toBe('Fire Red')
        ->and($options[0]['sku'])->toBe($group->sku)
        ->and($options[0]['redirect_url'])->toBe(route('admin.catalog.products.edit', $group->id));

    $response->assertJsonPath('page', 1)
        ->assertJsonPath('lastPage', 1)
        ->assertJsonPath('total', 1);
});

it('lists a variant_group\'s direct simple children when parent_id is passed', function () {
    [$configurable, $color, $size, $colorCode, $sizeCode] = makeConfigurableForVariantChildren();

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$sizeOptionCode,
        'values'    => ['common' => [$sizeCode => $sizeOptionCode]],
    ]);

    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
        ]))
        ->assertOk();

    $options = $response->json('options');

    expect($options)->toHaveCount(1)
        ->and($options[0]['id'])->toBe($leaf->id)
        ->and($options[0]['axisValues'])->toHaveKey($sizeCode)
        ->and($options[0]['sku'])->toBe($leaf->sku);
});

it('paginates children according to page/perPage', function () {
    [$configurable, , $size, , $sizeCode] = makeConfigurableForVariantChildren(extraSizeOptions: 5);

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => null,
        'group_axis_option' => null,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-group',
    ]);

    $optionCodes = $size->options()->orderBy('id')->pluck('code')->all();

    expect(count($optionCodes))->toBeGreaterThanOrEqual(5);

    $leafIds = [];

    foreach (array_slice($optionCodes, 0, 5) as $optionCode) {
        $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
            'parent_id' => $group->id,
            'sku'       => $configurable->sku.'-'.$optionCode,
            'values'    => ['common' => [$sizeCode => $optionCode]],
        ]);

        $leafIds[] = $leaf->id;
    }

    $admin = Admin::factory()->create();

    $firstPage = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
            'page'      => 1,
            'perPage'   => 2,
        ]))
        ->assertOk();

    $firstPage->assertJsonPath('page', 1)
        ->assertJsonPath('lastPage', 3)
        ->assertJsonPath('total', 5);

    expect($firstPage->json('options'))->toHaveCount(2)
        ->and(collect($firstPage->json('options'))->pluck('id')->all())->toBe(array_slice($leafIds, 0, 2));

    $secondPage = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
            'page'      => 2,
            'perPage'   => 2,
        ]))
        ->assertOk();

    $secondPage->assertJsonPath('page', 2)
        ->assertJsonPath('lastPage', 3);

    expect(collect($secondPage->json('options'))->pluck('id')->all())->toBe(array_slice($leafIds, 2, 2));
});

it('filters children by axis option label or sku via the query parameter', function () {
    [$configurable, , $size, , $sizeCode] = makeConfigurableForVariantChildren();

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => null,
        'group_axis_option' => null,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-group',
    ]);

    $smallOption = $size->options[0];
    $mediumOption = $size->options[1];

    // Distinct label/sku vocab per leaf so a "by label" query and a "by sku"
    // query each isolate exactly one leaf, never both.
    $smallOption->translateOrNew('en_US')->label = 'Crimson Tint';
    $smallOption->save();

    $mediumOption->translateOrNew('en_US')->label = 'Azure Tint';
    $mediumOption->save();

    $labelMatchLeaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-leaf-one',
        'values'    => ['common' => [$sizeCode => $smallOption->code]],
    ]);

    $skuMatchLeaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-uniquesku123',
        'values'    => ['common' => [$sizeCode => $mediumOption->code]],
    ]);

    $admin = Admin::factory()->create();

    $byLabel = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
            'query'     => 'Crimson',
        ]))
        ->assertOk();

    expect($byLabel->json('options'))->toHaveCount(1)
        ->and($byLabel->json('options')[0]['id'])->toBe($labelMatchLeaf->id);

    $bySku = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
            'query'     => 'uniquesku123',
        ]))
        ->assertOk();

    expect($bySku->json('options'))->toHaveCount(1)
        ->and($bySku->json('options')[0]['id'])->toBe($skuMatchLeaf->id);
});

it('rejects a parent_id outside the configurable subtree', function () {
    [$configurable, , , , $sizeCode] = makeConfigurableForVariantChildren();

    [$otherConfigurable] = makeConfigurableForVariantChildren();

    $foreignGroup = $otherConfigurable->getTypeInstance()->createVariantGroup($otherConfigurable, [
        'group_axis_code'   => null,
        'group_axis_option' => null,
        'group_values'      => [],
        'sku'               => $otherConfigurable->sku.'-group',
    ]);

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $foreignGroup->id,
            'axis'      => $sizeCode,
        ]))
        ->assertNotFound();
});

it('rejects an axis code that is not part of the configurable variant structure', function () {
    [$configurable] = makeConfigurableForVariantChildren();

    $foreignAxisCode = 'not_an_axis_'.Str::random(8);

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'axis' => $foreignAxisCode,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axis');
});

it('404s for a non-configurable product', function () {
    $simple = Product::factory()->simple()->create();

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $simple->id).'?'.http_build_query([
            'axis' => 'whatever',
        ]))
        ->assertNotFound();
});

it('reports variant complete/total for a group child and completeness for a leaf child', function () {
    [$configurable, $color, $size, $colorCode, $sizeCode] = makeConfigurableForVariantChildren();

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$sizeOptionCode,
        'values'    => ['common' => [$sizeCode => $sizeOptionCode]],
    ]);

    $channel = core()->getRequestedChannel();
    $locale = core()->getRequestedLocale();

    DB::table('product_completeness')->insert([
        'product_id'    => $leaf->id,
        'channel_id'    => $channel->id,
        'locale_id'     => $locale->id,
        'score'         => 100,
        'missing_count' => 0,
    ]);

    $admin = Admin::factory()->create();

    $groupOption = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'axis' => $colorCode,
        ]))
        ->assertOk()
        ->json('options.0');

    expect($groupOption['id'])->toBe($group->id)
        ->and($groupOption['completeness'])->toBeNull()
        ->and($groupOption['variantTotal'])->toBe(1)
        ->and($groupOption['variantComplete'])->toBe(1)
        ->and($groupOption)->toHaveKey('image')
        ->and($groupOption['image'])->toBeNull();

    $leafOption = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'parent_id' => $group->id,
            'axis'      => $sizeCode,
        ]))
        ->assertOk()
        ->json('options.0');

    expect($leafOption['id'])->toBe($leaf->id)
        ->and($leafOption['completeness'])->toBe(100)
        ->and($leafOption['variantTotal'])->toBeNull()
        ->and($leafOption['variantComplete'])->toBeNull();
});

it('reports a zero variant fraction for a group with no leaves', function () {
    [$configurable, $color, , $colorCode] = makeConfigurableForVariantChildren();

    $redOptionCode = $color->options->first()->code;

    $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.catalog.products.variant_children', $configurable->id).'?'.http_build_query([
            'axis' => $colorCode,
        ]))
        ->assertOk()
        ->assertJsonPath('options.0.completeness', null)
        ->assertJsonPath('options.0.variantTotal', 0)
        ->assertJsonPath('options.0.variantComplete', 0);
});
