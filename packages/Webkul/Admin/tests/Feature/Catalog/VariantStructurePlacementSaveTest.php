<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;

uses(DatabaseTransactions::class);

/**
 * A family with a select axis attribute plus one plain attribute, both mapped
 * into the family so they are valid placement targets.
 */
function makeFamilyForPlacementSave(): array
{
    $axis = Attribute::factory()->create(['code' => 'color_'.Str::random(8), 'type' => 'select']);
    $plain = Attribute::factory()->create(['code' => 'price_'.Str::random(8), 'type' => 'text']);

    $factory = AttributeFamily::factory();

    $family = $factory->create(['code' => 'fam_'.Str::random(8)]);

    $factory->linkAttributeGroupToFamily($family);

    $family->refresh();

    $factory->linkAttributesToFamily($family, $axis);
    $factory->linkAttributesToFamily($family, $plain);

    return [$family, $axis->code, $plain->code];
}

it('keeps an attribute assigned to a variant level when the structure is saved', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$family, $axisCode, $plainCode] = makeFamilyForPlacementSave();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 1,
    ]);

    $response = $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structure' => [
            'id'         => $structure->id,
            'code'       => $structure->code,
            'name'       => $structure->name,
            'levels'     => 1,
            'axes'       => ['level_1' => [$axisCode], 'level_2' => []],
            'placements' => [
                'common'     => [],
                'sub_parent' => [],
                'variant'    => [$plainCode],
            ],
        ],
    ])->assertOk();

    expect($response->json('data.placements.variant'))->toContain($plainCode);

    $reloaded = $this->getJson(route('admin.catalog.families.variant-structures.index', $family->id))
        ->assertOk()
        ->json('data');

    $saved = collect($reloaded)->firstWhere('id', $structure->id);

    expect($saved['placements']['variant'])->toContain($plainCode);
});
