<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * A configurable without a variant structure, with `$colors` × `$sizes`
 * simple variants. Codes are randomly suffixed because attribute codes are
 * globally unique and the suite runs against a seeded database.
 *
 * @return array{0: Product, 1: Attribute, 2: Attribute, 3: array<int, Product>}
 */
function makeLegacyConfigurableWithVariants(int $colors = 3, int $sizes = 4): array
{
    $color = Attribute::factory()->create(['code' => 'color_'.Str::random(8), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'size_'.Str::random(8), 'type' => 'select']);

    if ($sizes > 3) {
        AttributeOption::factory()->count($sizes - 3)->create(['attribute_id' => $size->id]);
    }

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    $configurable = app(ProductRepository::class)->create([
        'type'                => ProductType::Configurable->value,
        'attribute_family_id' => $family->id,
        'sku'                 => 'LEGACY-'.Str::random(8),
        'super_attributes'    => [$color->code, $size->code],
    ]);

    $type = $configurable->getTypeInstance();

    $variants = [];

    foreach ($color->options()->orderBy('id')->take($colors)->get() as $colorOption) {
        foreach ($size->options()->orderBy('id')->take($sizes)->get() as $sizeOption) {
            $variants[] = $type->createVariant($configurable, $configurable->super_attributes, [
                'parent_id' => $configurable->id,
                'sku'       => $configurable->sku.'-'.$colorOption->code.'-'.$sizeOption->code,
                'values'    => ['common' => [
                    $color->code => $colorOption->code,
                    $size->code  => $sizeOption->code,
                ]],
            ]);
        }
    }

    return [$configurable->refresh(), $color, $size, $variants];
}

it('pages a legacy configurable\'s variants ten at a time in id order', function () {
    [$configurable, , , $variants] = makeLegacyConfigurableWithVariants();

    $this->loginAsAdmin();

    $response = $this->getJson(route('admin.catalog.products.configurable_variants', $configurable->id))
        ->assertOk()
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.last_page', 2);

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toBe(collect($variants)->take(10)->pluck('id')->all());

    $second = $this->getJson(route('admin.catalog.products.configurable_variants', [$configurable->id, 'page' => 2]))
        ->assertOk();

    expect(collect($second->json('data'))->pluck('id')->all())
        ->toBe(collect($variants)->slice(10)->pluck('id')->values()->all());
});

it('returns only the sku and super attribute values of each variant', function () {
    [$configurable, $color, $size, $variants] = makeLegacyConfigurableWithVariants(1, 1);

    $this->loginAsAdmin();

    $row = $this->getJson(route('admin.catalog.products.configurable_variants', $configurable->id))
        ->assertOk()
        ->json('data.0');

    expect($row['id'])->toBe($variants[0]->id)
        ->and($row['sku'])->toBe($variants[0]->sku)
        ->and(array_keys($row['values']['common']))->toEqualCanonicalizing(['sku', $color->code, $size->code])
        ->and($row)->toHaveKey('image');
});

it('filters variants by super attribute option in SQL', function () {
    [$configurable, $color, $size] = makeLegacyConfigurableWithVariants();

    $gold = $color->options()->orderBy('id')->first()->code;
    $seven = $size->options()->orderBy('id')->skip(1)->first()->code;

    $this->loginAsAdmin();

    $byColor = $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'filters' => [$color->code => $gold],
    ]))->assertOk()->assertJsonPath('meta.total', 4);

    expect(collect($byColor->json('data'))->pluck('values.common.'.$color->code)->unique()->all())->toBe([$gold]);

    $byBoth = $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'filters' => [$color->code => $gold, $size->code => $seven],
    ]))->assertOk()->assertJsonPath('meta.total', 1);

    expect($byBoth->json('data.0.values.common'))->toMatchArray([$color->code => $gold, $size->code => $seven]);
});

it('ignores an empty filter value', function () {
    [$configurable, $color] = makeLegacyConfigurableWithVariants();

    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'filters' => [$color->code => ''],
    ]))->assertOk()->assertJsonPath('meta.total', 12);
});

it('rejects a filter on an attribute that is not a super attribute of the configurable', function () {
    [$configurable] = makeLegacyConfigurableWithVariants(1, 1);

    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'filters' => ['sku' => 'anything'],
    ]))->assertUnprocessable()->assertJsonValidationErrors('filters');
});

it('searches variants by sku case-insensitively', function () {
    [$configurable, , , $variants] = makeLegacyConfigurableWithVariants();

    $this->loginAsAdmin();

    $target = $variants[5];

    $response = $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'query' => strtoupper(Str::after($target->sku, $configurable->sku.'-')),
    ]))->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toContain($target->id)
        ->and($response->json('meta.total'))->toBeLessThan(12);
});

it('treats like wildcards in the sku search literally', function () {
    [$configurable] = makeLegacyConfigurableWithVariants(1, 2);

    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.configurable_variants', [$configurable->id, 'query' => '%']))
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('leaves out variants staged for removal so the page refills', function () {
    [$configurable, , , $variants] = makeLegacyConfigurableWithVariants();

    $this->loginAsAdmin();

    $response = $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'exclude' => [$variants[0]->id, $variants[1]->id],
    ]))->assertOk()->assertJsonPath('meta.total', 10);

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toBe(collect($variants)->slice(2)->pluck('id')->values()->all());
});

it('works the same when elasticsearch is enabled', function () {
    [$configurable, $color] = makeLegacyConfigurableWithVariants();

    config(['elasticsearch.enabled' => true]);

    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.configurable_variants', [
        $configurable->id,
        'filters' => [$color->code => $color->options()->orderBy('id')->first()->code],
    ]))->assertOk()->assertJsonPath('meta.total', 4);
});

it('denies the variants listing without the product edit permission', function () {
    [$configurable] = makeLegacyConfigurableWithVariants(1, 1);

    $this->loginWithPermissions('custom', ['catalog', 'catalog.products']);

    $this->getJson(route('admin.catalog.products.configurable_variants', $configurable->id))
        ->assertForbidden();
});

it('allows the variants listing with the product edit permission', function () {
    [$configurable] = makeLegacyConfigurableWithVariants(1, 1);

    $this->loginWithPermissions('custom', ['catalog', 'catalog.products', 'catalog.products.edit']);

    $this->getJson(route('admin.catalog.products.configurable_variants', $configurable->id))
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('returns not found for a product that is not configurable', function () {
    [, , , $variants] = makeLegacyConfigurableWithVariants(1, 1);

    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.configurable_variants', $variants[0]->id))
        ->assertNotFound();
});

it('no longer embeds every variant in the configurable edit page', function () {
    [$configurable, , , $variants] = makeLegacyConfigurableWithVariants();

    $this->loginAsAdmin();

    $response = $this->get(route('admin.catalog.products.edit', $configurable->id))->assertOk();

    $response->assertSee(route('admin.catalog.products.configurable_variants', $configurable->id), false);

    foreach ($variants as $variant) {
        $response->assertDontSee($variant->sku, false);
    }
});

it('keeps variants missing from a partial payload and deletes only the removed ones', function () {
    [$configurable, , , $variants] = makeLegacyConfigurableWithVariants(1, 3);

    [$otherConfigurable, , , $foreignVariants] = makeLegacyConfigurableWithVariants(1, 1);

    $configurable->getTypeInstance()->update([
        'sku'              => $configurable->sku,
        'values'           => $configurable->values,
        'variants_partial' => '1',
        'removed_variants' => [$variants[1]->id, $foreignVariants[0]->id],
    ], $configurable->id);

    expect(Product::whereKey($variants[0]->id)->exists())->toBeTrue()
        ->and(Product::whereKey($variants[2]->id)->exists())->toBeTrue()
        ->and(Product::whereKey($variants[1]->id)->exists())->toBeFalse()
        ->and(Product::whereKey($foreignVariants[0]->id)->exists())->toBeTrue();
});

it('still prunes variants missing from a full payload', function () {
    [$configurable, $color, $size, $variants] = makeLegacyConfigurableWithVariants(1, 2);

    $kept = $variants[0];

    $configurable->getTypeInstance()->update([
        'sku'      => $configurable->sku,
        'values'   => $configurable->values,
        'variants' => [
            $kept->id => [
                'sku'    => $kept->sku,
                'values' => ['common' => [
                    'sku'        => $kept->sku,
                    $color->code => $kept->values['common'][$color->code],
                    $size->code  => $kept->values['common'][$size->code],
                ]],
            ],
        ],
    ], $configurable->id);

    expect(Product::whereKey($kept->id)->exists())->toBeTrue()
        ->and(Product::whereKey($variants[1]->id)->exists())->toBeFalse();
});

it('saves a partial variants payload from the edit page without pruning other pages', function () {
    $this->loginAsAdmin();

    $configurable = seedRequiredProductValues(Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create());

    $attribute = $configurable->super_attributes->first();

    $existing = $configurable->variants->first();

    $newSku = $configurable->sku.'-variant_new';

    $this->put(route('admin.catalog.products.update', $configurable->id), [
        'sku'              => $configurable->sku,
        'values'           => $configurable->values,
        'variants_partial' => '1',
        'variants'         => [
            'variant_0' => [
                'sku'    => $newSku,
                'values' => ['common' => ['sku' => $newSku, $attribute->code => $attribute->options->last()->code]],
            ],
        ],
    ])->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    expect(Product::whereKey($existing->id)->exists())->toBeTrue()
        ->and(Product::where('parent_id', $configurable->id)->where('sku', $newSku)->exists())->toBeTrue();

    $this->put(route('admin.catalog.products.update', $configurable->id), [
        'sku'              => $configurable->sku,
        'values'           => $configurable->values,
        'variants_partial' => '1',
        'removed_variants' => [$existing->id],
    ])->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    expect(Product::whereKey($existing->id)->exists())->toBeFalse()
        ->and(Product::where('parent_id', $configurable->id)->where('sku', $newSku)->exists())->toBeTrue();
});
