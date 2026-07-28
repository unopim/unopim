<?php

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Catalog\AttributeFamilyController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

function assignGroupWithAttributes(AttributeFamily $family, AttributeGroup $group, array $attributes, int $position = 1): int
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

it('sends only the first page of groups to the editor, each without its attributes', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $attribute = Attribute::factory()->create();

    foreach (range(1, AttributeFamilyController::GROUPS_PER_PAGE + 5) as $position) {
        assignGroupWithAttributes($family, AttributeGroup::factory()->create(), [$attribute], $position);
    }

    $response = $this->get(route('admin.catalog.families.edit', $family->id))->assertOk();

    $payload = $response->original->getData()['attributeFamily'];

    expect($payload['familyGroupMappings'])->toHaveCount(AttributeFamilyController::GROUPS_PER_PAGE)
        ->and($payload['groupsTotal'])->toBe(AttributeFamilyController::GROUPS_PER_PAGE + 5)
        ->and($payload['groupsLastPage'])->toBe(2)
        ->and($payload['groupMappingIds'])->toHaveCount(AttributeFamilyController::GROUPS_PER_PAGE + 5);

    expect($payload['familyGroupMappings'][0]['customAttributes'])->toBe([])
        ->and($payload['familyGroupMappings'][0]['attributesLoaded'])->toBeFalse()
        ->and($payload['familyGroupMappings'][0]['attributesCount'])->toBe(1);
});

it('serves later pages of groups and filters them by a search term', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    foreach (range(1, AttributeFamilyController::GROUPS_PER_PAGE + 3) as $position) {
        assignGroupWithAttributes($family, AttributeGroup::factory()->create(), [], $position);
    }

    $needle = AttributeGroup::factory()->create(['code' => 'lazy_needle_group']);
    assignGroupWithAttributes($family, $needle, [], AttributeFamilyController::GROUPS_PER_PAGE + 4);

    $secondPage = $this->json('GET', route('admin.catalog.families.groups', $family->id), ['page' => 2])->assertOk();

    expect($secondPage->json('groups'))->toHaveCount(4)
        ->and($secondPage->json('lastPage'))->toBe(2);

    $searched = $this->json('GET', route('admin.catalog.families.groups', $family->id), ['query' => 'lazy_needle'])->assertOk();

    expect(collect($searched->json('groups'))->pluck('code')->all())->toBe([$needle->code]);
});

it('serves a single group\'s attributes on demand', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $group = AttributeGroup::factory()->create();
    $attribute = Attribute::factory()->create();

    assignGroupWithAttributes($family, $group, [$attribute]);

    $response = $this->json('GET', route('admin.catalog.families.group-attributes', [
        'id'      => $family->id,
        'groupId' => $group->id,
    ]))->assertOk();

    expect(collect($response->json('attributes'))->pluck('code')->all())->toBe([$attribute->code]);
});

it('keeps the attributes of a group the editor never loaded', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $group = AttributeGroup::factory()->create();
    $attribute = Attribute::factory()->create();

    $mappingId = assignGroupWithAttributes($family, $group, [$attribute]);

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'             => $family->code,
        'attribute_groups' => [
            $group->id => [
                'id'                       => $group->id,
                'attribute_groups_mapping' => $mappingId,
                'position'                 => 1,
                'attributes_loaded'        => 0,
            ],
        ],
    ])->assertRedirect();

    expect(DB::table('attribute_group_mappings')->where('attribute_family_group_id', $mappingId)->count())->toBe(1);
});

it('still clears a loaded group that the user emptied', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $group = AttributeGroup::factory()->create();
    $attribute = Attribute::factory()->create();

    $mappingId = assignGroupWithAttributes($family, $group, [$attribute]);

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'             => $family->code,
        'attribute_groups' => [
            $group->id => [
                'id'                       => $group->id,
                'attribute_groups_mapping' => $mappingId,
                'position'                 => 1,
                'attributes_loaded'        => 1,
            ],
        ],
    ])->assertRedirect();

    expect(DB::table('attribute_group_mappings')->where('attribute_family_group_id', $mappingId)->count())->toBe(0);
});

it('keeps groups that were merely on another page of the editor', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $submittedGroup = AttributeGroup::factory()->create();
    $otherPageGroup = AttributeGroup::factory()->create();

    $submittedMappingId = assignGroupWithAttributes($family, $submittedGroup, [], 1);
    $otherPageMappingId = assignGroupWithAttributes($family, $otherPageGroup, [], 2);

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'                     => $family->code,
        'retained_group_mappings'  => $submittedMappingId.','.$otherPageMappingId,
        'attribute_groups'         => [
            $submittedGroup->id => [
                'id'                       => $submittedGroup->id,
                'attribute_groups_mapping' => $submittedMappingId,
                'position'                 => 1,
                'attributes_loaded'        => 1,
            ],
        ],
    ])->assertRedirect();

    expect(DB::table('attribute_family_group_mappings')->where('id', $otherPageMappingId)->exists())->toBeTrue();
});

it('removes a group the editor dropped from its retained list', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $keptGroup = AttributeGroup::factory()->create();
    $removedGroup = AttributeGroup::factory()->create();

    $keptMappingId = assignGroupWithAttributes($family, $keptGroup, [], 1);
    $removedMappingId = assignGroupWithAttributes($family, $removedGroup, [], 2);

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'                    => $family->code,
        'retained_group_mappings' => (string) $keptMappingId,
        'attribute_groups'        => [
            $keptGroup->id => [
                'id'                       => $keptGroup->id,
                'attribute_groups_mapping' => $keptMappingId,
                'position'                 => 1,
                'attributes_loaded'        => 1,
            ],
        ],
    ])->assertRedirect();

    expect(DB::table('attribute_family_group_mappings')->where('id', $removedMappingId)->exists())->toBeFalse()
        ->and(DB::table('attribute_family_group_mappings')->where('id', $keptMappingId)->exists())->toBeTrue();
});

it('excludes a family\'s assigned attributes from the option list without listing their codes', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $group = AttributeGroup::factory()->create();
    $assigned = Attribute::factory()->create();
    $unassigned = Attribute::factory()->create();

    assignGroupWithAttributes($family, $group, [$assigned]);

    $response = $this->json('GET', route('admin.catalog.options.fetch-all'), [
        'entityName'  => 'attributes',
        'notInFamily' => $family->id,
        'perPage'     => 5000,
    ])->assertOk();

    $codes = collect($response->json('options'))->pluck('code');

    expect($codes)->not->toContain($assigned->code)
        ->and($codes)->toContain($unassigned->code);
});

it('keeps translations for locales the label switcher did not submit', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $family->translateOrNew('en_US')->name = 'Default family';
    $family->translateOrNew('fr_FR')->name = 'Famille par défaut';
    $family->save();

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'  => $family->code,
        'en_US' => ['name' => 'Renamed family'],
    ])->assertRedirect();

    $family->refresh();

    expect($family->translate('en_US')->name)->toBe('Renamed family')
        ->and($family->translate('fr_FR')->name)->toBe('Famille par défaut');
});
