<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

function assignFamilyGroup(AttributeFamily $family, AttributeGroup $group, array $attributes, int $position = 1): int
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

    return $mappingId;
}

it('counts every attribute assigned to the family', function () {
    $family = AttributeFamily::factory()->create();

    assignFamilyGroup($family, AttributeGroup::factory()->create(), Attribute::factory()->count(3)->create()->all(), 1);
    assignFamilyGroup($family, AttributeGroup::factory()->create(), Attribute::factory()->count(2)->create()->all(), 2);

    expect($family->attributeCount())->toBe(5);
});

it('loads the attributes of one group only, in mapping order', function () {
    $family = AttributeFamily::factory()->create();

    $wanted = Attribute::factory()->count(2)->create();
    $other = Attribute::factory()->count(3)->create();

    $group = AttributeGroup::factory()->create();

    assignFamilyGroup($family, $group, $wanted->all(), 1);
    assignFamilyGroup($family, AttributeGroup::factory()->create(), $other->all(), 2);

    $attributes = $family->customAttributesForGroup($group->id);

    expect($attributes->pluck('code')->all())->toBe($wanted->pluck('code')->all());
});

it('resolves a group by code and falls back to the first group by position', function () {
    $family = AttributeFamily::factory()->create();

    $first = AttributeGroup::factory()->create();
    $second = AttributeGroup::factory()->create();

    assignFamilyGroup($family, $second, [], 2);
    assignFamilyGroup($family, $first, [], 1);

    expect($family->groupSummaryByCode('en_US', $second->code)->id)->toBe($second->id)
        ->and($family->groupSummaryByCode('en_US', 'does-not-exist')->id)->toBe($first->id)
        ->and($family->groupSummaryByCode('en_US')->id)->toBe($first->id);
});

it('returns null when the family has no groups', function () {
    expect(AttributeFamily::factory()->create()->groupSummaryByCode('en_US'))->toBeNull();
});
