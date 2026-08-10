<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Builds a 2-level (color/size) configurable + variant structure with a
 * common attribute set only on the root. Globally-unique codes since this
 * suite runs against a live/seeded DB.
 */
function apiInheritanceFixture(): array
{
    $commonCode = 'cmn_'.Str::random(8);
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    Attribute::factory()->create([
        'code'              => $commonCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $commonCode,
        $colorCode,
        $sizeCode,
    ])->get());

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
        'sku'                  => 'CFG-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    $configurable->values = [
        'common' => [
            'sku'       => $configurable->sku,
            $commonCode => 'PARENT-NAME-VALUE',
        ],
    ];
    $configurable->save();

    $type = $configurable->getTypeInstance();

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

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

    $directChild = $type->createVariant($configurable, [$color], [
        'parent_id' => $configurable->id,
        'sku'       => $configurable->sku.'-direct-'.$redOptionCode,
        'values'    => ['common' => [$colorCode => $redOptionCode]],
    ]);

    return [
        'configurable' => $configurable->fresh(),
        'group'        => $group->fresh(),
        'leaf'         => $leaf->fresh(),
        'directChild'  => $directChild->fresh(),
        'commonCode'   => $commonCode,
    ];
}

it('includes the root parent\'s inherited common attribute value when GETting a 2-level leaf variant', function () {
    $fixture = apiInheritanceFixture();

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $fixture['leaf']->sku]))
        ->assertOk();

    expect($response->json('values.common.'.$fixture['commonCode']))->toBe('PARENT-NAME-VALUE');
});

it('includes the root parent\'s inherited common attribute value when GETting a 1-level direct child', function () {
    $fixture = apiInheritanceFixture();

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $fixture['directChild']->sku]))
        ->assertOk();

    expect($response->json('values.common.'.$fixture['commonCode']))->toBe('PARENT-NAME-VALUE');
});

it('includes the root parent\'s own common attribute value when GETting the root itself via the configurable-products endpoint', function () {
    $fixture = apiInheritanceFixture();

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.get', ['code' => $fixture['configurable']->sku]))
        ->assertOk();

    expect($response->json('values.common.'.$fixture['commonCode']))->toBe('PARENT-NAME-VALUE');
});

it('includes the inherited common attribute value for a 1-level direct child in the product listing endpoint', function () {
    $fixture = apiInheritanceFixture();

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.index', [
            'filters' => json_encode([
                'sku' => [[
                    'operator' => '=',
                    'value'    => $fixture['directChild']->sku,
                ]],
            ]),
        ]))
        ->assertOk();

    $records = collect($response->json('data'));

    $directChildRecord = $records->firstWhere('sku', $fixture['directChild']->sku);

    expect($directChildRecord)->not->toBeNull()
        ->and($directChildRecord['values']['common'][$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});
