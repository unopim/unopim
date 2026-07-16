<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Contracts\VariantPlacementSuggester;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Repositories\VariantStructureRepository;

uses(DatabaseTransactions::class);

it('binds the variant structure contract to the model via the repository', function () {
    $repository = app(VariantStructureRepository::class);

    expect($repository->getModel())->toBeInstanceOf(VariantStructure::class);
});

it('creates a variant structure with axes and placements', function () {
    $family = AttributeFamily::factory()->create();

    $structure = app(VariantStructureRepository::class)->create([
        'attribute_family_id' => $family->id,
        'code'                => 'tshirt',
        'name'                => 'T-Shirt',
        'levels'              => 2,
    ]);

    expect($structure)->toBeInstanceOf(VariantStructure::class)
        ->and($structure->levels)->toBe(2);

    $this->assertDatabaseHas('variant_structures', [
        'attribute_family_id' => $family->id,
        'code'                => 'tshirt',
        'levels'              => 2,
    ]);

    $attribute = Attribute::query()->first() ?? Attribute::factory()->create();

    $structure->placements()->create([
        'attribute_id' => $attribute->id,
        'level'        => 'variant',
    ]);

    expect($structure->fresh()->placements)->toHaveCount(1)
        ->and($structure->fresh()->placements->first()->level)->toBe('variant');
});

it('binds the placement suggester contract', function () {
    expect(app(VariantPlacementSuggester::class))
        ->toBeInstanceOf(Webkul\Product\Services\VariantPlacementSuggester::class);
});
