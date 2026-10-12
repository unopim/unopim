<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;

function familyAudits(int $familyId): Collection
{
    return DB::table('audits')
        ->where('tags', 'attributeFamily')
        ->where('history_id', $familyId)
        ->get();
}

it('records history when a family group gains an attribute', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $mapping = $family->attributeFamilyGroupMappings()->first();

    $existing = DB::table('attribute_group_mappings')
        ->where('attribute_family_group_id', $mapping->id)
        ->orderBy('position')
        ->pluck('attribute_id')
        ->all();

    $newAttribute = Attribute::query()->whereNotIn('id', $existing)->first();

    $before = familyAudits($family->id)->count();

    $payload = [
        'code'             => $family->code,
        'attribute_groups' => [
            $mapping->attribute_group_id => [
                'attribute_groups_mapping' => $mapping->id,
                'position'                 => $mapping->position,
                'custom_attributes'        => collect($existing)
                    ->push($newAttribute->id)
                    ->values()
                    ->map(fn ($id, $index) => ['id' => $id, 'position' => $index + 1])
                    ->all(),
            ],
        ],
        'retained_group_mappings' => $family->attributeFamilyGroupMappings()->pluck('id')->implode(','),
    ];

    $this->put(route('admin.catalog.families.update', $family->id), $payload)
        ->assertRedirect();

    expect(familyAudits($family->id)->count())->toBeGreaterThan($before);
});

it('records history when the family name changes', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $before = familyAudits($family->id)->count();

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'                    => $family->code,
        'en_US'                   => ['name' => 'Renamed '.uniqid()],
        'retained_group_mappings' => $family->attributeFamilyGroupMappings()->pluck('id')->implode(','),
    ])->assertRedirect();

    expect(familyAudits($family->id)->count())->toBeGreaterThan($before);
});
