<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

/**
 * Guards the product edit render path against the attribute-metadata N+1s
 * described in the "Product Detail Page — Performance Analysis" (F1).
 *
 * The edit page renders one group at a time via AttributeGroup::customAttributes()
 * / AttributeFamily::customAttributes(), then calls $field->translate() per
 * attribute to resolve its label. If the translations relation is not eager
 * loaded by those methods, every label lookup is a separate query.
 */
function buildFamilyWithTranslatedAttributes(int $count): array
{
    $family = AttributeFamily::factory()->create();

    $group = AttributeGroup::factory()->create();

    $family->familyGroups()->attach($group);

    $mapping = $family->attributeFamilyGroupMappings()->first();

    $attributes = collect(range(1, $count))->map(function (int $i) use ($mapping): Attribute {
        $attribute = Attribute::factory()->create(['type' => 'text']);

        $attribute->translateOrNew('en_US')->name = "Label {$i}";
        $attribute->save();

        $mapping->customAttributes()->attach($attribute, ['position' => $i]);

        return $attribute;
    });

    return [$family, $group, $attributes];
}

it('eager loads attribute name translations from AttributeGroup::customAttributes (F1)', function () {
    [$family, $group] = buildFamilyWithTranslatedAttributes(5);

    $attributes = $group->customAttributes($family->id);

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach ($attributes as $attribute) {
        $attribute->translate('en_US')?->name;
    }

    expect(DB::getQueryLog())->toHaveCount(0);
});

it('eager loads attribute name translations from AttributeFamily::customAttributes (F1)', function () {
    [$family] = buildFamilyWithTranslatedAttributes(5);

    $attributes = $family->customAttributes()->get();

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach ($attributes as $attribute) {
        $attribute->translate('en_US')?->name;
    }

    expect(DB::getQueryLog())->toHaveCount(0);
});
