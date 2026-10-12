<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Models\CompletenessSetting;

function tabFamilyAudits(int $familyId): int
{
    return DB::table('audits')
        ->where('tags', 'attributeFamily')
        ->where('history_id', $familyId)
        ->count();
}

function familyAxisCode(AttributeFamily $family): string
{
    $axis = $family->getConfigurableAttributes()->first();

    expect($axis)->not->toBeNull('family needs a configurable attribute to use as a variant axis');

    return $axis->code;
}

it('records family history when one variant structure is saved', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $before = tabFamilyAudits($family->id);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structure' => [
            'code'       => 'hist_single_'.uniqid(),
            'levels'     => 1,
            'axes'       => ['level_1' => [familyAxisCode($family)]],
            'placements' => [],
        ],
    ])->assertOk();

    expect(tabFamilyAudits($family->id))->toBeGreaterThan($before);
});

it('records family history when variant structures are replaced in bulk', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $before = tabFamilyAudits($family->id);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [[
            'code'       => 'hist_bulk_'.uniqid(),
            'levels'     => 1,
            'axes'       => ['level_1' => [familyAxisCode($family)]],
            'placements' => [],
        ]],
    ])->assertOk();

    expect(tabFamilyAudits($family->id))->toBeGreaterThan($before);
});

it('records family history when completeness settings change', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $attributeId = DB::table('attribute_group_mappings')
        ->join('attribute_family_group_mappings', 'attribute_family_group_mappings.id', '=', 'attribute_group_mappings.attribute_family_group_id')
        ->where('attribute_family_group_mappings.attribute_family_id', $family->id)
        ->value('attribute_group_mappings.attribute_id');

    $channelCode = DB::table('channels')->value('code');

    $before = tabFamilyAudits($family->id);

    $this->postJson(route('admin.catalog.families.completeness.update'), [
        'familyId'             => $family->id,
        'attributeId'          => $attributeId,
        'channel_requirements' => $channelCode,
    ])->assertSuccessful();

    expect(CompletenessSetting::query()
        ->where('family_id', $family->id)
        ->where('attribute_id', $attributeId)
        ->exists())->toBeTrue('the completeness setting must actually have been written');

    expect(tabFamilyAudits($family->id))->toBeGreaterThan($before);
});

it('records family history when completeness settings change in bulk', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $attributeIds = DB::table('attribute_group_mappings')
        ->join('attribute_family_group_mappings', 'attribute_family_group_mappings.id', '=', 'attribute_group_mappings.attribute_family_group_id')
        ->where('attribute_family_group_mappings.attribute_family_id', $family->id)
        ->limit(2)
        ->pluck('attribute_group_mappings.attribute_id')
        ->all();

    $channelCode = DB::table('channels')->value('code');

    $before = tabFamilyAudits($family->id);

    $this->postJson(route('admin.catalog.families.completeness.mass_update'), [
        'familyId'             => $family->id,
        'indices'              => $attributeIds,
        'channel_requirements' => $channelCode,
    ])->assertSuccessful();

    expect(tabFamilyAudits($family->id))->toBeGreaterThan($before);
});

it('leaves no history when a completeness save changes nothing', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $attributeId = DB::table('attribute_group_mappings')
        ->join('attribute_family_group_mappings', 'attribute_family_group_mappings.id', '=', 'attribute_group_mappings.attribute_family_group_id')
        ->where('attribute_family_group_mappings.attribute_family_id', $family->id)
        ->value('attribute_group_mappings.attribute_id');

    $channelCode = DB::table('channels')->value('code');

    $this->postJson(route('admin.catalog.families.completeness.update'), [
        'familyId'             => $family->id,
        'attributeId'          => $attributeId,
        'channel_requirements' => $channelCode,
    ])->assertSuccessful();

    $after = tabFamilyAudits($family->id);

    $this->postJson(route('admin.catalog.families.completeness.update'), [
        'familyId'             => $family->id,
        'attributeId'          => $attributeId,
        'channel_requirements' => $channelCode,
    ])->assertSuccessful();

    expect(tabFamilyAudits($family->id))->toBe($after);
});
