<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;

uses(DatabaseTransactions::class);

function familyWithStructureAxis(): array
{
    $axis = Attribute::factory()->create(['code' => 'axis_'.Str::random(8), 'type' => 'select']);

    $factory = AttributeFamily::factory();

    $family = $factory->create(['code' => 'fam_'.Str::random(8)]);

    $factory->linkAttributeGroupToFamily($family);

    $family->refresh();

    $factory->linkAttributesToFamily($family, $axis);

    $family->refresh();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 1,
    ]);

    VariantStructureAxis::create([
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $axis->id,
        'level'                => 'level_1',
        'position'             => 1,
    ]);

    return [$family, $axis, $structure];
}

function detachAxisFromFamily(AttributeFamily $family, Attribute $axis): void
{
    $mappingIds = $family->attributeFamilyGroupMappings()->pluck('id');

    DB::table('attribute_group_mappings')
        ->whereIn('attribute_family_group_id', $mappingIds)
        ->where('attribute_id', $axis->id)
        ->delete();

    $family->refresh();
}

it('hides a variant structure whose axis attribute left the family', function () {
    $this->loginAsAdmin();

    [$family, $axis, $structure] = familyWithStructureAxis();

    detachAxisFromFamily($family, $axis);

    $response = $this->json('POST', route('admin.catalog.products.store'), [
        'sku'                 => 'sku_'.Str::random(8),
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
    ]);

    $response->assertStatus(422);

    expect($response->json('errors.attribute_family_id'))->not->toBeNull();
});

it('rejects creating a configurable product on a structure with a missing axis attribute', function () {
    $this->loginAsAdmin();

    [$family, $axis, $structure] = familyWithStructureAxis();

    detachAxisFromFamily($family, $axis);

    $this->json('POST', route('admin.catalog.products.store'), [
        'sku'                  => 'sku_'.Str::random(8),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ])->assertStatus(422)->assertJsonPath('errors.variant_structure_id.0', trans('admin::app.catalog.products.index.create.invalid-variant-structure'));
});

it('still offers a variant structure whose axis attributes are all assigned', function () {
    $this->loginAsAdmin();

    [$family, , $structure] = familyWithStructureAxis();

    $response = $this->json('POST', route('admin.catalog.products.store'), [
        'sku'                 => 'sku_'.Str::random(8),
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
    ]);

    $response->assertStatus(200);

    expect(collect($response->json('data.variant_structures'))->pluck('id'))->toContain($structure->id);
});
