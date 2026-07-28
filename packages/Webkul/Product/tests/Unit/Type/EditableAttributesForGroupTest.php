<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Product\Models\Product;

function mapGroupToFamily(AttributeFamily $family, AttributeGroup $group, iterable $attributes, int $position): void
{
    $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
        'attribute_family_id' => $family->id,
        'attribute_group_id'  => $group->id,
        'position'            => $position,
    ]);

    foreach ($attributes as $index => $attribute) {
        DB::table('attribute_group_mappings')->insert([
            'attribute_family_group_id' => $mappingId,
            'attribute_id'              => $attribute->id,
            'position'                  => $index + 1,
        ]);
    }
}

it('returns only the requested group of the family', function () {
    $family = AttributeFamily::factory()->create();

    $inGroup = Attribute::factory()->count(2)->create();
    $elsewhere = Attribute::factory()->count(2)->create();

    $group = AttributeGroup::factory()->create();

    mapGroupToFamily($family, $group, $inGroup, 1);
    mapGroupToFamily($family, AttributeGroup::factory()->create(), $elsewhere, 2);

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => $family->id,
    ]);

    $codes = $product->getEditableAttributesForGroup($group->id)->pluck('code')->all();

    expect($codes)->toBe($inGroup->pluck('code')->all());
});

it('returns an empty collection for a group that is not in the family', function () {
    $product = Product::factory()->create(['type' => 'simple']);

    expect($product->getEditableAttributesForGroup(987654)->all())->toBe([]);
});
