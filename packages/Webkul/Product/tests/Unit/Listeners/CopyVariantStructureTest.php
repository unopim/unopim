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

/**
 * Dropping the axis level on clone collapsed every axis onto the same level,
 * which then collided on the (structure, level, position) unique key and 500'd
 * the "create family based on ..." flow.
 */
it('keeps each axis at its own level when cloning', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('vs_levels_source');

    $colour = Attribute::factory()->create();
    $size = Attribute::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $source->id,
        'code'                => 'vs_levels_structure',
        'name'                => 'Colour / Size',
        'levels'              => 2,
    ]);

    $structure->axes()->create(['attribute_id' => $colour->id, 'level' => 'level_1', 'position' => 0]);
    $structure->axes()->create(['attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0]);

    $clone = app(AttributeFamilyRepository::class)->createScaffolded('vs_levels_clone', $source->id);

    $cloned = VariantStructure::where('attribute_family_id', $clone->id)->with('axes')->first();

    expect($cloned->axes->pluck('level', 'attribute_id')->all())->toBe([
        $colour->id => 'level_1',
        $size->id   => 'level_2',
    ]);
});

it('clones several axes that share one level', function () {
    $source = app(AttributeFamilyRepository::class)->createScaffolded('vs_multi_source');

    $attributes = collect(range(0, 2))->map(fn () => Attribute::factory()->create());

    $structure = VariantStructure::create([
        'attribute_family_id' => $source->id,
        'code'                => 'vs_multi_structure',
        'name'                => 'Colour + Size + Brand',
        'levels'              => 1,
    ]);

    $attributes->each(fn ($attribute, $position) => $structure->axes()->create([
        'attribute_id' => $attribute->id,
        'level'        => 'level_1',
        'position'     => $position,
    ]));

    $clone = app(AttributeFamilyRepository::class)->createScaffolded('vs_multi_clone', $source->id);

    $cloned = VariantStructure::where('attribute_family_id', $clone->id)->with('axes')->first();

    expect($cloned->axes)->toHaveCount(3)
        ->and($cloned->axes->pluck('position')->sort()->values()->all())->toBe([0, 1, 2])
        ->and($cloned->axes->pluck('level')->unique()->values()->all())->toBe(['level_1']);
});
