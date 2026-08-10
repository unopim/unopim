<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * A structureless configurable carrying two select super attributes. Codes are
 * randomly suffixed because this suite runs against a live, seeded database
 * where `attributes.code` is globally unique.
 *
 * @return array{0: Product, 1: Attribute, 2: Attribute}
 */
function makeConfigurableForUniquenessCheck(): array
{
    $color = Attribute::factory()->create(['code' => 'color_'.Str::random(8), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'size_'.Str::random(8), 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$color, $size]));

    $configurable = app(ProductRepository::class)->create([
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
        'sku'                 => 'CVU-'.Str::random(8),
        'super_attributes'    => [$color->code, $size->code],
    ]);

    return [$configurable, $color, $size];
}

function postVariantUniquenessCheck($test, array $payload)
{
    return $test->postJson(route('admin.catalog.products.check-variant'), $payload);
}

it('rejects a variant attribute key that is not an axis of the parent and never compiles it into a json path', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color] = makeConfigurableForUniquenessCheck();

    $statements = [];

    DB::listen(function ($query) use (&$statements): void {
        $statements[] = $query->sql;
    });

    postVariantUniquenessCheck($this, [
        'parentId'          => $configurable->id,
        'sku'               => 'CVU-INJECTED',
        'variantAttributes' => [$color->code."'||(select current_setting('is_superuser'))||'" => 'red'],
    ])->assertStatus(422)->assertJsonValidationErrors('variantAttributes');

    $injected = array_values(array_filter(
        $statements,
        fn (string $sql): bool => str_contains($sql, 'current_setting')
    ));

    expect($injected)->toBe([]);
});

it('rejects a variant attribute key belonging to another product', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable] = makeConfigurableForUniquenessCheck();

    [, $foreignColor] = makeConfigurableForUniquenessCheck();

    postVariantUniquenessCheck($this, [
        'parentId'          => $configurable->id,
        'sku'               => 'CVU-FOREIGN',
        'variantAttributes' => [$foreignColor->code => 'red'],
    ])->assertStatus(422)->assertJsonValidationErrors('variantAttributes');
});

it('rejects a uniqueness check against a parent that does not exist', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    postVariantUniquenessCheck($this, [
        'parentId'          => 99999999,
        'sku'               => 'CVU-MISSING',
        'variantAttributes' => ['color' => 'red'],
    ])->assertStatus(422)->assertJsonValidationErrors('variantAttributes');
});

it('reports a legitimate axis combination as unique when no sibling holds it', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $size] = makeConfigurableForUniquenessCheck();

    postVariantUniquenessCheck($this, [
        'parentId'          => $configurable->id,
        'sku'               => 'CVU-UNIQUE-'.Str::random(6),
        'variantAttributes' => [
            $color->code => $color->options->first()->code,
            $size->code  => $size->options->first()->code,
        ],
    ])->assertOk()->assertExactJson([]);
});

it('reports a legitimate axis combination as taken when a sibling already holds it', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $size] = makeConfigurableForUniquenessCheck();

    $colorOption = $color->options->first()->code;
    $sizeOption = $size->options->first()->code;

    Product::factory()->create([
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $configurable->attribute_family_id,
        'sku'                 => $configurable->sku.'-taken',
        'values'              => [
            'common' => [
                $color->code => $colorOption,
                $size->code  => $sizeOption,
            ],
        ],
    ]);

    postVariantUniquenessCheck($this, [
        'parentId'          => $configurable->id,
        'sku'               => 'CVU-TAKEN-'.Str::random(6),
        'variantAttributes' => [
            $color->code => $colorOption,
            $size->code  => $sizeOption,
        ],
    ])->assertOk()->assertJsonPath(
        'errors.message',
        trans('admin::app.catalog.products.edit.types.configurable.variant-exists')
    );
});

it('excludes the variant under edit from its own uniqueness check', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $size] = makeConfigurableForUniquenessCheck();

    $colorOption = $color->options->first()->code;
    $sizeOption = $size->options->first()->code;

    $variant = Product::factory()->create([
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $configurable->attribute_family_id,
        'sku'                 => $configurable->sku.'-self',
        'values'              => [
            'common' => [
                $color->code => $colorOption,
                $size->code  => $sizeOption,
            ],
        ],
    ]);

    postVariantUniquenessCheck($this, [
        'parentId'          => $configurable->id,
        'sku'               => $variant->sku,
        'variantId'         => $variant->id,
        'variantAttributes' => [
            $color->code => $colorOption,
            $size->code  => $sizeOption,
        ],
    ])->assertOk()->assertExactJson([]);
});
