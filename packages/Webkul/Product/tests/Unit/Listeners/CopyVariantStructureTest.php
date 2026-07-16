<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Models\VariantStructure;

it('clones the variant structure of the source family', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('vs_source');

    $colour = Attribute::factory()->create();
    $size = Attribute::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $source->id,
        'code'                => 'vs_source_structure',
        'name'                => 'Colour / Size',
        'levels'              => 2,
    ]);

    $structure->axes()->create(['attribute_id' => $colour->id, 'position' => 1]);
    $structure->axes()->create(['attribute_id' => $size->id, 'position' => 2]);
    $structure->placements()->create(['attribute_id' => $size->id, 'level' => 2]);

    $clone = app(AttributeFamilyRepository::class)->createScaffolded('vs_clone', $source->id);

    $cloned = VariantStructure::where('attribute_family_id', $clone->id)->first();

    expect($cloned)->not->toBeNull();
    expect($cloned->levels)->toBe(2);
    expect($cloned->id)->not->toBe($structure->id);
    expect($cloned->axes()->pluck('attribute_id')->all())->toBe([$colour->id, $size->id]);
    expect($cloned->axes()->pluck('position')->all())->toBe([1, 2]);
    expect($cloned->placements()->pluck('level')->all())->toBe(['sub_parent']);
});

it('does nothing when the source family has no variant structure', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('vs_none_source');

    $clone = app(AttributeFamilyRepository::class)->createScaffolded('vs_none_clone', $source->id);

    expect(VariantStructure::where('attribute_family_id', $clone->id)->count())->toBe(0);
});
