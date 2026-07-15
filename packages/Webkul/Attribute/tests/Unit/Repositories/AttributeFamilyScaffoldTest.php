<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;

it('stores a status when creating a family', function () {
    $family = AttributeFamily::create([
        'code'   => 'test_family_status',
        'status' => 1,
    ]);

    expect($family->refresh()->status)->toBe(1);
});

it('rejects a duplicate family code at the database level', function () {
    AttributeFamily::create(['code' => 'dupe_family', 'status' => 1]);

    expect(fn () => AttributeFamily::create(['code' => 'dupe_family', 'status' => 1]))
        ->toThrow(QueryException::class);
});

it('scaffolds a general group holding sku when no source family is given', function () {
    $family = app(AttributeFamilyRepository::class)->createScaffolded('scaffold_default');

    $mappings = $family->attributeFamilyGroupMappings()->get();

    expect($mappings)->toHaveCount(1);
    expect($mappings->first()->attributeGroups->first()->code)->toBe('general');
    expect($mappings->first()->customAttributes->pluck('code')->all())->toBe(['sku']);
    expect($family->status)->toBe(1);
});

it('recreates the general group when it has been deleted', function () {
    AttributeGroup::where('code', 'general')->delete();

    $family = app(AttributeFamilyRepository::class)->createScaffolded('scaffold_recreated');

    expect(AttributeGroup::where('code', 'general')->count())->toBe(1);
    expect($family->attributeFamilyGroupMappings()->first()->customAttributes->pluck('code')->all())
        ->toBe(['sku']);
});

it('clones groups, attributes and positions from the source family', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('scaffold_source');

    $group = AttributeGroup::create(['code' => 'marketing']);
    $attribute = Attribute::factory()->create();

    $mapping = $source->attributeFamilyGroupMappings()->create([
        'attribute_group_id' => $group->id,
        'position'           => 2,
    ]);
    $mapping->customAttributes()->save($attribute, ['position' => 1]);

    $groupCountBefore = AttributeGroup::count();

    $clone = app(AttributeFamilyRepository::class)->createScaffolded('scaffold_clone', $source->id);

    $sourceShape = familyShape($source);
    $cloneShape = familyShape($clone->refresh());

    expect($cloneShape)->toBe($sourceShape);
    expect(AttributeGroup::count())->toBe($groupCountBefore);
    expect($clone->id)->not->toBe($source->id);
});

it('dispatches the copied event only when cloning', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('scaffold_evt_source');

    Event::fake();

    app(AttributeFamilyRepository::class)->createScaffolded('scaffold_evt_plain');

    Event::assertNotDispatched('catalog.attribute_family.copied');

    app(AttributeFamilyRepository::class)->createScaffolded('scaffold_evt_clone', $source->id);

    Event::assertDispatched('catalog.attribute_family.copied');
});

/**
 * Group code + position => ordered attribute codes, for structural comparison.
 */
function familyShape($family): array
{
    return $family->attributeFamilyGroupMappings()->get()->mapWithKeys(fn ($mapping) => [
        $mapping->attributeGroups->first()->code.':'.$mapping->position => $mapping
            ->customAttributes()
            ->orderBy('attribute_group_mappings.position')
            ->get()
            ->pluck('code')
            ->all(),
    ])->all();
}
