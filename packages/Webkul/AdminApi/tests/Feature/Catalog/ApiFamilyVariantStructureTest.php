<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * A family carrying a two-level structure plus spare attributes for the
 * placement and eligibility cases.
 */
function apiVsFixture(string $prefix): array
{
    $family = AttributeFamily::factory()->create();

    $color = Attribute::factory()->create(['code' => $prefix.'_color_'.uniqid(), 'type' => 'select']);
    $brand = Attribute::factory()->create(['code' => $prefix.'_brand_'.uniqid(), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $prefix.'_size_'.uniqid(), 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => $prefix.'_material_'.uniqid(), 'type' => 'text']);
    $localised = Attribute::factory()->create([
        'code'             => $prefix.'_localised_'.uniqid(),
        'type'             => 'select',
        'value_per_locale' => true,
    ]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily(
        $family,
        Attribute::whereIn('id', [$color->id, $brand->id, $size->id, $material->id, $localised->id])->get()
    );

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => $prefix.'_structure',
        'name'                => $prefix.' structure',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $brand->id, 'level' => 'level_1', 'position' => 1],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $material->id, 'level' => 'common'],
    ]);

    return compact('family', 'structure', 'color', 'brand', 'size', 'material', 'localised');
}

/**
 * Attach a configurable product to the structure, optionally giving it a child
 * so the structure counts as owning a built tree.
 */
function apiVsAttachProduct(array $fixture, string $sku, bool $withVariant): Product
{
    $root = app(ProductRepository::class)->create([
        'sku'                  => $sku,
        'type'                 => 'configurable',
        'attribute_family_id'  => $fixture['family']->id,
        'variant_structure_id' => $fixture['structure']->id,
    ]);

    if ($withVariant) {
        Product::create([
            'sku'                 => $sku.'-child',
            'type'                => 'simple',
            'parent_id'           => $root->id,
            'attribute_family_id' => $fixture['family']->id,
        ]);
    }

    return $root;
}

/**
 * A single-level structure in the same family, created directly because the API
 * cannot change `levels` once a structure exists.
 */
function apiVsOneLevelStructure(array $fixture, string $code): VariantStructure
{
    $structure = VariantStructure::create([
        'attribute_family_id' => $fixture['family']->id,
        'code'                => $code,
        'name'                => $code,
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $fixture['color']->id, 'level' => 'level_1', 'position' => 0],
    ]);

    return $structure;
}

function apiVsRoute(string $name, array $fixture, ?string $structureCode = null): string
{
    return route('admin.api.families-variant-structures.'.$name, array_filter([
        'code'          => $fixture['family']->code,
        'structureCode' => $structureCode ?? $fixture['structure']->code,
    ]));
}

function apiVsStoreRoute(array $fixture): string
{
    return route('admin.api.families-variant-structures.store', ['code' => $fixture['family']->code]);
}

/**
 * A well formed two-level create body, with any part of it overridable.
 */
function apiVsCreateBody(array $fixture, string $code, array $overrides = []): array
{
    return array_replace([
        'code'   => $code,
        'name'   => 'Structure '.$code,
        'levels' => 2,
        'axes'   => [
            'level_1' => [$fixture['color']->code, $fixture['brand']->code],
            'level_2' => [$fixture['size']->code],
        ],
    ], $overrides);
}

function apiVsStoredStructure(array $fixture, string $code): ?VariantStructure
{
    return VariantStructure::query()
        ->where('attribute_family_id', $fixture['family']->id)
        ->where('code', $code)
        ->first();
}

/** Reading */
it('lists the variant structures of a family in the paginated envelope', function () {
    $fixture = apiVsFixture('vsl1');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('index', $fixture))
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'last_page', 'total', 'links']);

    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.code'))->toBe($fixture['structure']->code);
    expect($response->json('data.0.family'))->toBe($fixture['family']->code);
});

it('does not list structures belonging to another family', function () {
    $first = apiVsFixture('vsl2a');
    $second = apiVsFixture('vsl2b');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('index', $first))
        ->assertOk();

    expect($response->json('total'))->toBe(1);
    expect(collect($response->json('data'))->pluck('code')->all())
        ->not->toContain($second['structure']->code);
});

it('returns one variant structure with its axes and placements grouped by level', function () {
    $fixture = apiVsFixture('vsg1');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture))
        ->assertOk();

    expect($response->json('code'))->toBe($fixture['structure']->code);
    expect($response->json('name'))->toBe($fixture['structure']->name);
    expect($response->json('family'))->toBe($fixture['family']->code);
    expect($response->json('levels'))->toBe(2);
    expect($response->json('axes'))->toBe([
        'level_1' => [$fixture['color']->code, $fixture['brand']->code],
        'level_2' => [$fixture['size']->code],
    ]);
    expect($response->json('placements'))->toBe([
        'common'     => [$fixture['material']->code],
        'sub_parent' => [],
        'variant'    => [],
    ]);
    expect($response->json())->toHaveKeys(['created_at', 'updated_at']);
});

it('orders axes within a level by the stored position', function () {
    $fixture = apiVsFixture('vsg2');

    VariantStructureAxis::query()
        ->where('variant_structure_id', $fixture['structure']->id)
        ->where('attribute_id', $fixture['color']->id)
        ->update(['position' => 5]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture))
        ->assertOk();

    expect($response->json('axes.level_1'))->toBe([$fixture['brand']->code, $fixture['color']->code]);
});

it('returns 404 for an unknown family on the listing endpoint', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.families-variant-structures.index', ['code' => 'vs-no-such-family']))
        ->assertNotFound();
});

it('returns 404 for an unknown family on the single endpoint', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.families-variant-structures.get', [
            'code'          => 'vs-no-such-family',
            'structureCode' => 'whatever',
        ]))
        ->assertNotFound();
});

it('returns 404 for an unknown structure code within a known family', function () {
    $fixture = apiVsFixture('vsg3');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture, 'vs-no-such-structure'))
        ->assertNotFound();

    expect($response->json('message'))->toContain('vs-no-such-structure');
});

it('returns 404 for a structure that belongs to a different family', function () {
    $first = apiVsFixture('vsg4a');
    $second = apiVsFixture('vsg4b');

    $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $first, $second['structure']->code))
        ->assertNotFound();
});

/** Effective placements */
function apiVsFamilyCodes(array $fixture): array
{
    return $fixture['family']->customAttributes()->pluck('attributes.code')->unique()->values()->all();
}

it('reports the tier that governs every family attribute on a two level structure', function () {
    $fixture = apiVsFixture('vse1');

    VariantStructureAttribute::query()->where('variant_structure_id', $fixture['structure']->id)->delete();

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture))
        ->assertOk();

    $effective = $response->json('effective_placements');

    expect(collect($effective['sub_parent'])->sort()->values()->all())
        ->toBe(collect([$fixture['color']->code, $fixture['brand']->code])->sort()->values()->all());
    expect($effective['variant'])->toBe([$fixture['size']->code]);
    expect($effective['common'])->toContain($fixture['material']->code);
    expect($effective['common'])->toContain($fixture['localised']->code);
    expect($effective['common'])->not->toContain($fixture['color']->code);

    $listed = [...$effective['common'], ...$effective['sub_parent'], ...$effective['variant']];

    expect(collect($listed)->sort()->values()->all())
        ->toBe(collect(apiVsFamilyCodes($fixture))->sort()->values()->all());
    expect(count($listed))->toBe(count(array_unique($listed)));
});

it('reports a single level structure axis as variant rather than sub parent', function () {
    $fixture = apiVsFixture('vse2');
    $oneLevel = apiVsOneLevelStructure($fixture, 'vse2_one_level');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture, $oneLevel->code))
        ->assertOk();

    $effective = $response->json('effective_placements');

    expect($response->json('levels'))->toBe(1);
    expect($effective['variant'])->toBe([$fixture['color']->code]);
    expect($effective['sub_parent'])->toBe([]);
    expect($effective['common'])->toContain($fixture['brand']->code);
    expect($effective['common'])->toContain($fixture['size']->code);
});

it('lets an explicit placement row override the common default', function () {
    $fixture = apiVsFixture('vse3');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['variant' => [$fixture['material']->code]],
        ])
        ->assertOk();

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture))
        ->assertOk();

    $effective = $response->json('effective_placements');

    expect($effective['variant'])->toContain($fixture['material']->code);
    expect($effective['common'])->not->toContain($fixture['material']->code);
    expect($effective['common'])->toContain($fixture['localised']->code);
});

it('includes the effective placements on the listing endpoint', function () {
    $fixture = apiVsFixture('vse4');

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('index', $fixture))
        ->assertOk();

    expect($response->json('data.0.effective_placements.variant'))->toBe([$fixture['size']->code]);
    expect(collect($response->json('data.0.effective_placements.sub_parent'))->sort()->values()->all())
        ->toBe(collect([$fixture['color']->code, $fixture['brand']->code])->sort()->values()->all());
    expect($response->json('data.0.effective_placements.common'))->toContain($fixture['localised']->code);
});

it('ignores effective placements sent back on a write and creates no placement rows', function () {
    $fixture = apiVsFixture('vse5');

    $before = VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->count();

    $body = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture))
        ->assertOk()
        ->json();

    expect($body['effective_placements']['common'])->not->toBeEmpty();

    $body['name'] = 'round tripped';

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), $body)
        ->assertOk();

    $after = VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->count();

    expect($after)->toBe($before);
    expect($fixture['structure']->fresh()->name)->toBe('round tripped');

    $placed = VariantStructureAttribute::query()
        ->where('variant_structure_id', $fixture['structure']->id)
        ->pluck('attribute_id')
        ->all();

    expect($placed)->toBe([$fixture['material']->id]);
});

/** Writing — unused structures */
it('updates the writable fields of an unused structure through PUT', function () {
    $fixture = apiVsFixture('vsu1');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'name'       => 'reshaped',
            'placements' => [
                'common'  => [$fixture['material']->code],
                'variant' => [$fixture['localised']->code],
            ],
        ])
        ->assertOk();

    $response = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($response->json('name'))->toBe('reshaped');
    expect($response->json('axes'))->toBe([
        'level_1' => [$fixture['color']->code, $fixture['brand']->code],
        'level_2' => [$fixture['size']->code],
    ]);
    expect($response->json('placements'))->toBe([
        'common'     => [$fixture['material']->code],
        'sub_parent' => [],
        'variant'    => [$fixture['localised']->code],
    ]);
});

it('accepts a PUT carrying only a name', function () {
    $fixture = apiVsFixture('vsu2');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), ['name' => 'label only'])
        ->assertOk();

    $response = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($response->json('name'))->toBe('label only');
    expect($response->json('levels'))->toBe(2);
    expect($response->json('axes.level_1'))->toBe([$fixture['color']->code, $fixture['brand']->code]);
    expect($response->json('axes.level_2'))->toBe([$fixture['size']->code]);
});

it('preserves the stored axis rows and positions across a PUT', function () {
    $fixture = apiVsFixture('vsu3');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), ['name' => 'untouched shape'])
        ->assertOk();

    $positions = VariantStructureAxis::query()
        ->where('variant_structure_id', $fixture['structure']->id)
        ->get()
        ->mapWithKeys(fn ($axis): array => [$axis->attribute_id => $axis->level.':'.$axis->position]);

    expect($positions)->toHaveCount(3);
    expect($positions[$fixture['color']->id])->toBe('level_1:0');
    expect($positions[$fixture['brand']->id])->toBe('level_1:1');
    expect($positions[$fixture['size']->id])->toBe('level_2:0');
});

/** Immutability of levels and axes */
it('accepts a PUT echoing back the exact stored levels and axes', function () {
    $fixture = apiVsFixture('vsi1');

    $body = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk()->json();

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'name'       => 'echoed back',
            'levels'     => $body['levels'],
            'axes'       => $body['axes'],
            'placements' => $body['placements'],
        ])
        ->assertOk();

    $after = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($after->json('name'))->toBe('echoed back');
    expect($after->json('levels'))->toBe($body['levels']);
    expect($after->json('axes'))->toBe($body['axes']);
    expect($after->json('placements'))->toBe($body['placements']);
});

it('rejects a PUT that changes the axis membership', function () {
    $fixture = apiVsFixture('vsi2');

    $response = $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'axes' => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    expect(json_encode($response->json('errors')))->toContain('cannot be modified');
    expect(VariantStructureAxis::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(3);
});

it('rejects a PUT that only reorders the axes within a level', function () {
    $fixture = apiVsFixture('vsi3');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'axes' => [
                'level_1' => [$fixture['brand']->code, $fixture['color']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    $positions = VariantStructureAxis::query()
        ->where('variant_structure_id', $fixture['structure']->id)
        ->get()
        ->mapWithKeys(fn ($axis): array => [$axis->attribute_id => $axis->position]);

    expect((int) $positions[$fixture['color']->id])->toBe(0);
    expect((int) $positions[$fixture['brand']->id])->toBe(1);
});

it('rejects a PUT that moves an axis between levels', function () {
    $fixture = apiVsFixture('vsi4');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'axes' => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['brand']->code, $fixture['size']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');
});

it('rejects a PUT that changes the levels value', function () {
    $fixture = apiVsFixture('vsi5');

    $response = $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), ['levels' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    expect(json_encode($response->json('errors')))->toContain('levels');
    expect((int) $fixture['structure']->fresh()->levels)->toBe(2);
});

it('rejects a PATCH that changes the axes', function () {
    $fixture = apiVsFixture('vsi6');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => ['level_1' => [$fixture['color']->code], 'level_2' => [$fixture['size']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');
});

it('rejects an axes block that drops a level entirely', function () {
    $fixture = apiVsFixture('vsi7');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'axes' => ['level_1' => [$fixture['color']->code, $fixture['brand']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');
});

it('clears the explicit placements when PUT omits them', function () {
    $fixture = apiVsFixture('vsu4');

    expect(VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(1);

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), ['name' => 'placements cleared'])
        ->assertOk();

    expect(VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(0);
});

it('updates only the name through PATCH and leaves the shape untouched', function () {
    $fixture = apiVsFixture('vsu5');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), ['name' => 'renamed only'])
        ->assertOk();

    $response = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($response->json('name'))->toBe('renamed only');
    expect($response->json('levels'))->toBe(2);
    expect($response->json('axes.level_1'))->toBe([$fixture['color']->code, $fixture['brand']->code]);
    expect($response->json('placements.common'))->toBe([$fixture['material']->code]);
});

it('updates only the placements through PATCH', function () {
    $fixture = apiVsFixture('vsu6');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['variant' => [$fixture['material']->code]],
        ])
        ->assertOk();

    $response = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($response->json('placements'))->toBe([
        'common'     => [],
        'sub_parent' => [],
        'variant'    => [$fixture['material']->code],
    ]);
    expect($response->json('axes.level_1'))->toBe([$fixture['color']->code, $fixture['brand']->code]);
});

/** Writing — structures already owning a tree */
it('rejects a levels change on a structure that already has variants', function () {
    $fixture = apiVsFixture('vsf1');
    apiVsAttachProduct($fixture, 'vsf1-config', true);

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), ['levels' => 1])
        ->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('cannot be modified');
    expect((int) $fixture['structure']->fresh()->levels)->toBe(2);
});

it('rejects an axes change on a structure that already has variants', function () {
    $fixture = apiVsFixture('vsf2');
    apiVsAttachProduct($fixture, 'vsf2-config', true);

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    expect(json_encode($response->json('errors')))->toContain('cannot be modified');
    expect($response->json('errors'))->not->toHaveKey('placements');
    expect(VariantStructureAxis::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(3);
});

it('allows a placements change on a structure that already has variants', function () {
    $fixture = apiVsFixture('vsf3');
    apiVsAttachProduct($fixture, 'vsf3-config', true);

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['variant' => [$fixture['material']->code]],
        ])
        ->assertOk();

    expect($response->json('data.placements'))->toBe([
        'common'     => [],
        'sub_parent' => [],
        'variant'    => [$fixture['material']->code],
    ]);

    $placement = VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->first();

    expect($placement->level)->toBe('variant');
});

it('allows a full placements rewrite through PUT on a structure that already has variants', function () {
    $fixture = apiVsFixture('vsf8');
    apiVsAttachProduct($fixture, 'vsf8-config', true);

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'name'       => 'placements rewritten in use',
            'placements' => [
                'sub_parent' => [$fixture['material']->code],
                'variant'    => [$fixture['localised']->code],
            ],
        ])
        ->assertOk();

    $response = $this->withHeaders($this->headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();

    expect($response->json('placements'))->toBe([
        'common'     => [],
        'sub_parent' => [$fixture['material']->code],
        'variant'    => [$fixture['localised']->code],
    ]);
    expect($response->json('effective_placements.sub_parent'))->toContain($fixture['material']->code);
});

it('still allows a name-only PATCH on a structure that already has variants', function () {
    $fixture = apiVsFixture('vsf4');
    apiVsAttachProduct($fixture, 'vsf4-config', true);

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), ['name' => 'renamed while in use'])
        ->assertOk();

    expect($fixture['structure']->fresh()->name)->toBe('renamed while in use');
});

it('accepts a PUT that restates the identical shape of a structure in use', function () {
    $fixture = apiVsFixture('vsf5');
    apiVsAttachProduct($fixture, 'vsf5-config', true);

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'name'   => 'same shape new name',
            'levels' => 2,
            'axes'   => [
                'level_1' => [$fixture['color']->code, $fixture['brand']->code],
                'level_2' => [$fixture['size']->code],
            ],
            'placements' => ['common' => [$fixture['material']->code]],
        ])
        ->assertOk();

    expect($fixture['structure']->fresh()->name)->toBe('same shape new name');
});

it('rejects an axes change even while the referencing product has no variants yet', function () {
    $fixture = apiVsFixture('vsf6');
    apiVsAttachProduct($fixture, 'vsf6-config', false);

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    expect(VariantStructureAxis::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(3);
});

it('allows a placements change while the referencing product has no variants yet', function () {
    $fixture = apiVsFixture('vsf7');
    apiVsAttachProduct($fixture, 'vsf7-config', false);

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['variant' => [$fixture['material']->code]],
        ])
        ->assertOk();

    $placement = VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->first();

    expect($placement->level)->toBe('variant');
});

/** Validation */
it('rejects a levels value outside one and two', function () {
    $fixture = apiVsFixture('vsv1');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), ['levels' => 3])
        ->assertStatus(422)
        ->assertJsonValidationErrors('levels');
});

it('accepts a PUT that omits levels and axes entirely', function () {
    $fixture = apiVsFixture('vsv2');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), ['name' => 'no shape needed'])
        ->assertOk();

    expect($fixture['structure']->fresh()->name)->toBe('no shape needed');
});

it('rejects an unknown axis attribute as an immutable change rather than an axis error', function () {
    $fixture = apiVsFixture('vsv6');

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => ['level_1' => ['vsv6-ghost-attribute'], 'level_2' => [$fixture['size']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');

    expect($response->json('errors'))->not->toHaveKey('axes');
});

it('rejects an empty axes block as an immutable change', function () {
    $fixture = apiVsFixture('vsv3');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => ['level_1' => [], 'level_2' => []],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('immutable');
});

it('rejects an unknown placement level key', function () {
    $fixture = apiVsFixture('vsv11');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['nowhere' => [$fixture['material']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements');
});

it('rejects a placement attribute that is not in the family', function () {
    $fixture = apiVsFixture('vsv12');
    $outsider = Attribute::factory()->create(['code' => 'vsv12_outsider_'.uniqid(), 'type' => 'text']);

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['common' => [$outsider->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.common');
});

it('rejects an explicit placement for an attribute that is already an axis', function () {
    $fixture = apiVsFixture('vsv13');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => ['common' => [$fixture['color']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.common');
});

it('rejects a sub parent placement on a single level structure', function () {
    $fixture = apiVsFixture('vsv14');
    $oneLevel = apiVsOneLevelStructure($fixture, 'vsv14_one_level');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture, $oneLevel->code), [
            'placements' => ['sub_parent' => [$fixture['material']->code]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.sub_parent');
});

it('rejects the same attribute placed at two levels', function () {
    $fixture = apiVsFixture('vsv15');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'placements' => [
                'common'  => [$fixture['material']->code],
                'variant' => [$fixture['material']->code],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.variant');
});

it('rejects an unknown axis level key', function () {
    $fixture = apiVsFixture('vsv16');

    $this->withHeaders($this->headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), [
            'axes' => ['level_1' => [$fixture['color']->code], 'level_9' => ['x']],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('leaves the stored axes untouched when a write is rejected', function () {
    $fixture = apiVsFixture('vsv17');

    $this->withHeaders($this->headers)
        ->json('PUT', apiVsRoute('update', $fixture), [
            'levels' => 2,
            'axes'   => [
                'level_1' => [$fixture['size']->code],
                'level_2' => [$fixture['material']->code],
            ],
        ])
        ->assertStatus(422);

    $stored = VariantStructureAxis::query()
        ->where('variant_structure_id', $fixture['structure']->id)
        ->orderBy('level')
        ->orderBy('position')
        ->get();

    expect($stored)->toHaveCount(3);
    expect($stored->pluck('attribute_id')->all())
        ->toBe([$fixture['color']->id, $fixture['brand']->id, $fixture['size']->id]);
    expect(VariantStructureAttribute::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(1);
});

/** Deletion */
it('deletes an unused structure together with its axis and placement rows', function () {
    $fixture = apiVsFixture('vsd1');
    $structureId = $fixture['structure']->id;

    $this->withHeaders($this->headers)
        ->json('DELETE', apiVsRoute('delete', $fixture))
        ->assertOk();

    expect(VariantStructure::find($structureId))->toBeNull();
    expect(VariantStructureAxis::where('variant_structure_id', $structureId)->count())->toBe(0);
    expect(VariantStructureAttribute::where('variant_structure_id', $structureId)->count())->toBe(0);
});

it('refuses to delete a structure a product still references', function () {
    $fixture = apiVsFixture('vsd2');
    apiVsAttachProduct($fixture, 'vsd2-config', false);

    $response = $this->withHeaders($this->headers)
        ->json('DELETE', apiVsRoute('delete', $fixture))
        ->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('cannot be deleted');
    expect(VariantStructure::find($fixture['structure']->id))->not->toBeNull();
});

it('refuses to delete a structure that already owns a variant tree', function () {
    $fixture = apiVsFixture('vsd3');
    apiVsAttachProduct($fixture, 'vsd3-config', true);

    $this->withHeaders($this->headers)
        ->json('DELETE', apiVsRoute('delete', $fixture))
        ->assertStatus(422);

    expect(VariantStructure::find($fixture['structure']->id))->not->toBeNull();
});

it('returns 404 when deleting an unknown structure', function () {
    $fixture = apiVsFixture('vsd4');

    $this->withHeaders($this->headers)
        ->json('DELETE', apiVsRoute('delete', $fixture, 'vsd4-ghost'))
        ->assertNotFound();
});

/** Creation */
it('creates a two level structure and returns it with its effective placements', function () {
    $fixture = apiVsFixture('vsc1');

    $response = $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsc1_created', [
            'placements' => [
                'common'     => [],
                'sub_parent' => [$fixture['material']->code],
                'variant'    => [$fixture['localised']->code],
            ],
        ]))
        ->assertStatus(201);

    expect($response->json('data.code'))->toBe('vsc1_created');
    expect($response->json('data.name'))->toBe('Structure vsc1_created');
    expect($response->json('data.family'))->toBe($fixture['family']->code);
    expect($response->json('data.levels'))->toBe(2);
    expect($response->json('data.axes'))->toBe([
        'level_1' => [$fixture['color']->code, $fixture['brand']->code],
        'level_2' => [$fixture['size']->code],
    ]);
    expect($response->json('data.placements'))->toBe([
        'common'     => [],
        'sub_parent' => [$fixture['material']->code],
        'variant'    => [$fixture['localised']->code],
    ]);
    expect($response->json('data.effective_placements.variant'))
        ->toBe([$fixture['size']->code, $fixture['localised']->code]);
    expect(collect($response->json('data.effective_placements.sub_parent'))->sort()->values()->all())
        ->toBe(collect([$fixture['color']->code, $fixture['brand']->code, $fixture['material']->code])->sort()->values()->all());
    expect($response->json('data'))->toHaveKeys(['created_at', 'updated_at']);
});

it('persists the created axes at the level and position the payload stated', function () {
    $fixture = apiVsFixture('vsc2');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsc2_created'))
        ->assertStatus(201);

    $structure = apiVsStoredStructure($fixture, 'vsc2_created');

    expect($structure)->not->toBeNull();
    expect((int) $structure->levels)->toBe(2);

    $axes = VariantStructureAxis::query()
        ->where('variant_structure_id', $structure->id)
        ->orderBy('level')
        ->orderBy('position')
        ->get();

    expect($axes)->toHaveCount(3);
    expect($axes->pluck('attribute_id')->all())
        ->toBe([$fixture['color']->id, $fixture['brand']->id, $fixture['size']->id]);
    expect($axes->map(fn ($axis): string => $axis->level.':'.$axis->position)->all())
        ->toBe(['level_1:0', 'level_1:1', 'level_2:0']);
});

it('reads back a created structure through the single structure endpoint', function () {
    $fixture = apiVsFixture('vsc3');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsc3_created'))
        ->assertStatus(201);

    $response = $this->withHeaders($this->headers)
        ->json('GET', apiVsRoute('get', $fixture, 'vsc3_created'))
        ->assertOk();

    expect($response->json('axes'))->toBe([
        'level_1' => [$fixture['color']->code, $fixture['brand']->code],
        'level_2' => [$fixture['size']->code],
    ]);
});

it('falls back to the code when the create body carries no name', function () {
    $fixture = apiVsFixture('vsc4');

    $body = apiVsCreateBody($fixture, 'vsc4_created');
    unset($body['name']);

    $response = $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), $body)
        ->assertStatus(201);

    expect($response->json('data.name'))->toBe('vsc4_created');
});

it('creates a single level structure whose axis governs the variant tier', function () {
    $fixture = apiVsFixture('vsc5');

    $response = $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsc5_created', [
            'levels' => 1,
            'axes'   => ['level_1' => [$fixture['color']->code]],
        ]))
        ->assertStatus(201);

    expect($response->json('data.levels'))->toBe(1);
    expect($response->json('data.axes'))->toBe([
        'level_1' => [$fixture['color']->code],
        'level_2' => [],
    ]);
    expect($response->json('data.effective_placements.variant'))->toBe([$fixture['color']->code]);
    expect($response->json('data.effective_placements.sub_parent'))->toBe([]);
});

it('rejects a second level of axes on a single level structure', function () {
    $fixture = apiVsFixture('vsc6');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsc6_created', [
            'levels' => 1,
            'axes'   => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes.level_2');

    expect(apiVsStoredStructure($fixture, 'vsc6_created'))->toBeNull();
});

it('rejects a code the family already carries', function () {
    $fixture = apiVsFixture('vsc7');

    $response = $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, $fixture['structure']->code))
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');

    expect(VariantStructure::where('attribute_family_id', $fixture['family']->id)->count())->toBe(1);
    expect(VariantStructureAxis::where('variant_structure_id', $fixture['structure']->id)->count())->toBe(3);
});

it('accepts the same structure code under a different family', function () {
    $first = apiVsFixture('vsc8a');
    $second = apiVsFixture('vsc8b');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($first), apiVsCreateBody($first, 'vsc8_shared'))
        ->assertStatus(201);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($second), apiVsCreateBody($second, 'vsc8_shared'))
        ->assertStatus(201);

    expect(apiVsStoredStructure($first, 'vsc8_shared'))->not->toBeNull();
    expect(apiVsStoredStructure($second, 'vsc8_shared'))->not->toBeNull();
});

it('returns 404 when creating a structure for an unknown family', function () {
    $fixture = apiVsFixture('vsc9');

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.families-variant-structures.store', ['code' => 'vsc9-no-such-family']),
            apiVsCreateBody($fixture, 'vsc9_created'))
        ->assertNotFound();
});

/** Creation — validation */
it('rejects a create body without a code', function () {
    $fixture = apiVsFixture('vscv1');

    $body = apiVsCreateBody($fixture, 'unused');
    unset($body['code']);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), $body)
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('rejects a structure code carrying anything but letters digits and underscore', function () {
    $fixture = apiVsFixture('vscv2');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv2-hyphenated'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');

    expect(apiVsStoredStructure($fixture, 'vscv2-hyphenated'))->toBeNull();
});

it('rejects a create body without levels', function () {
    $fixture = apiVsFixture('vscv3');

    $body = apiVsCreateBody($fixture, 'vscv3_created');
    unset($body['levels']);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), $body)
        ->assertStatus(422)
        ->assertJsonValidationErrors('levels');
});

it('rejects a create levels value outside one and two', function () {
    $fixture = apiVsFixture('vscv4');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv4_created', ['levels' => 3]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('levels');
});

it('rejects a create body without axes', function () {
    $fixture = apiVsFixture('vscv5');

    $body = apiVsCreateBody($fixture, 'vscv5_created');
    unset($body['axes']);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), $body)
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('rejects an empty first axis level on create', function () {
    $fixture = apiVsFixture('vscv6');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv6_created', [
            'axes' => ['level_1' => [], 'level_2' => [$fixture['size']->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes.level_1');
});

it('rejects a two level create that states no second axis level', function () {
    $fixture = apiVsFixture('vscv7');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv7_created', [
            'axes' => ['level_1' => [$fixture['color']->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes.level_2');

    expect(apiVsStoredStructure($fixture, 'vscv7_created'))->toBeNull();
});

it('rejects an axis attribute that does not exist', function () {
    $fixture = apiVsFixture('vscv8');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv8_created', [
            'axes' => [
                'level_1' => ['vscv8_ghost_attribute'],
                'level_2' => [$fixture['size']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('rejects an axis attribute that belongs to no family', function () {
    $fixture = apiVsFixture('vscv9');
    $outsider = Attribute::factory()->create(['code' => 'vscv9_outsider_'.uniqid(), 'type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv9_created', [
            'axes' => [
                'level_1' => [$outsider->code],
                'level_2' => [$fixture['size']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('rejects an axis attribute the family holds but which is not axis eligible', function () {
    $fixture = apiVsFixture('vscv10');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv10_created', [
            'axes' => [
                'level_1' => [$fixture['localised']->code],
                'level_2' => [$fixture['size']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');

    expect(apiVsStoredStructure($fixture, 'vscv10_created'))->toBeNull();
});

it('rejects the same attribute used as an axis on both levels', function () {
    $fixture = apiVsFixture('vscv11');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv11_created', [
            'axes' => [
                'level_1' => [$fixture['color']->code, $fixture['brand']->code],
                'level_2' => [$fixture['color']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('rejects an unknown axis level key on create', function () {
    $fixture = apiVsFixture('vscv12');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv12_created', [
            'axes' => [
                'level_1' => [$fixture['color']->code],
                'level_2' => [$fixture['size']->code],
                'level_9' => [$fixture['brand']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('axes');
});

it('rejects an unknown placement level key on create', function () {
    $fixture = apiVsFixture('vscv13');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv13_created', [
            'placements' => ['nowhere' => [$fixture['material']->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements');
});

it('rejects a placement attribute that is not in the family on create', function () {
    $fixture = apiVsFixture('vscv14');
    $outsider = Attribute::factory()->create(['code' => 'vscv14_outsider_'.uniqid(), 'type' => 'text']);

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv14_created', [
            'placements' => ['common' => [$outsider->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.common');
});

it('rejects an explicit placement for an attribute created as an axis', function () {
    $fixture = apiVsFixture('vscv15');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv15_created', [
            'placements' => ['common' => [$fixture['color']->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.common');
});

it('rejects the same attribute placed at two levels on create', function () {
    $fixture = apiVsFixture('vscv16');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv16_created', [
            'placements' => [
                'common'  => [$fixture['material']->code],
                'variant' => [$fixture['material']->code],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.variant');
});

it('rejects a sub parent placement on a single level create', function () {
    $fixture = apiVsFixture('vscv17');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv17_created', [
            'levels'     => 1,
            'axes'       => ['level_1' => [$fixture['color']->code]],
            'placements' => ['sub_parent' => [$fixture['material']->code]],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('placements.sub_parent');
});

it('writes neither the structure nor its axes when a create is rejected', function () {
    $fixture = apiVsFixture('vscv18');

    $before = VariantStructureAxis::query()->count();

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscv18_created', [
            'placements' => ['common' => [$fixture['color']->code]],
        ]))
        ->assertStatus(422);

    expect(apiVsStoredStructure($fixture, 'vscv18_created'))->toBeNull();
    expect(VariantStructureAxis::query()->count())->toBe($before);
});

/** Creation — usable straight away */
it('creates a structure a configurable product can reference immediately', function () {
    $fixture = apiVsFixture('vscu1');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vscu1_created'))
        ->assertStatus(201);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.configurable_products.store'), [
            'family'            => $fixture['family']->code,
            'variant_structure' => 'vscu1_created',
            'values'            => ['common' => ['sku' => 'vscu1-config']],
        ])
        ->assertStatus(201);

    $product = Product::where('sku', 'vscu1-config')->first();

    expect($product->variant_structure_id)->toBe(apiVsStoredStructure($fixture, 'vscu1_created')->id);
});

/** Method surface */
it('does not expose a create endpoint on a single variant structure', function () {
    $fixture = apiVsFixture('vsm1');

    $this->withHeaders($this->headers)
        ->json('POST', apiVsRoute('get', $fixture), ['name' => 'vsm1-new'])
        ->assertStatus(405);
});

/** Authentication and authorisation */
it('rejects an unauthenticated request to the listing endpoint', function () {
    $fixture = apiVsFixture('vsa1');

    $this->json('GET', apiVsRoute('index', $fixture))->assertUnauthorized();
});

it('rejects an unauthenticated write', function () {
    $fixture = apiVsFixture('vsa2');

    $this->json('PATCH', apiVsRoute('patch', $fixture), ['name' => 'nope'])->assertUnauthorized();
});

it('forbids reading variant structures without the read permission', function () {
    $fixture = apiVsFixture('vsa3');
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', apiVsRoute('index', $fixture))->assertForbidden();
    $this->withHeaders($headers)->json('GET', apiVsRoute('get', $fixture))->assertForbidden();
});

it('rejects an unauthenticated create', function () {
    $fixture = apiVsFixture('vsa9');

    $this->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsa9_created'))
        ->assertUnauthorized();
});

it('forbids creating a variant structure without the create permission', function () {
    $fixture = apiVsFixture('vsa10');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures']);

    $this->withHeaders($headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsa10_created'))
        ->assertForbidden();

    expect(apiVsStoredStructure($fixture, 'vsa10_created'))->toBeNull();
});

it('allows creating a variant structure with the create permission', function () {
    $fixture = apiVsFixture('vsa11');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures.create']);

    $this->withHeaders($headers)
        ->json('POST', apiVsStoreRoute($fixture), apiVsCreateBody($fixture, 'vsa11_created'))
        ->assertStatus(201);

    expect(apiVsStoredStructure($fixture, 'vsa11_created'))->not->toBeNull();
});

it('forbids writing variant structures without the edit permission', function () {
    $fixture = apiVsFixture('vsa4');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures']);

    $this->withHeaders($headers)->json('PUT', apiVsRoute('update', $fixture), [])->assertForbidden();
    $this->withHeaders($headers)->json('PATCH', apiVsRoute('patch', $fixture), [])->assertForbidden();
});

it('forbids deleting variant structures without the delete permission', function () {
    $fixture = apiVsFixture('vsa5');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures']);

    $this->withHeaders($headers)->json('DELETE', apiVsRoute('delete', $fixture))->assertForbidden();
});

it('allows reading variant structures with the read permission', function () {
    $fixture = apiVsFixture('vsa6');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures']);

    $this->withHeaders($headers)->json('GET', apiVsRoute('index', $fixture))->assertOk();
    $this->withHeaders($headers)->json('GET', apiVsRoute('get', $fixture))->assertOk();
});

it('allows writing variant structures with the edit permission', function () {
    $fixture = apiVsFixture('vsa7');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures.edit']);

    $this->withHeaders($headers)
        ->json('PATCH', apiVsRoute('patch', $fixture), ['name' => 'permitted rename'])
        ->assertOk();

    expect($fixture['structure']->fresh()->name)->toBe('permitted rename');
});

it('allows deleting variant structures with the delete permission', function () {
    $fixture = apiVsFixture('vsa8');
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.variant_structures.delete']);

    $this->withHeaders($headers)->json('DELETE', apiVsRoute('delete', $fixture))->assertOk();

    expect(VariantStructure::find($fixture['structure']->id))->toBeNull();
});
