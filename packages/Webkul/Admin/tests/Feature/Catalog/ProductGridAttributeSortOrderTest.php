<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();
});

function setCommonValue(Product $product, string $code, ?string $value): void
{
    $values = $product->values ?? [];

    if ($value === null) {
        unset($values['common'][$code]);
    } else {
        $values['common'][$code] = $value;
    }

    $product->values = $values;
    $product->save();
}

function gridSkusSortedBy(string $code, string $order, string $skuPrefix = 'sort-'): array
{
    return collect(
        test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), [
                'filters'    => ['sku' => [$skuPrefix]],
                'sort'       => ['column' => $code, 'order' => $order],
                'pagination' => ['page' => 1, 'per_page' => 50],
            ])->assertOk()->json('records')
    )->pluck('sku')->all();
}

it('orders products by their real decimal values without rounding them together', function () {
    $code = 'sort_dec_'.Str::lower(Str::random(6));

    Attribute::factory()->validation_decimal()->create(['code' => $code, 'type' => 'text']);

    $high = Product::factory()->simple()->create(['sku' => 'sort-high']);
    $mid = Product::factory()->simple()->create(['sku' => 'sort-mid']);
    $low = Product::factory()->simple()->create(['sku' => 'sort-low']);

    setCommonValue($high, $code, '2.20');
    setCommonValue($mid, $code, '2.10');
    setCommonValue($low, $code, '1.80');

    $asc = array_values(array_intersect(gridSkusSortedBy($code, 'asc'), ['sort-high', 'sort-mid', 'sort-low']));
    $desc = array_values(array_intersect(gridSkusSortedBy($code, 'desc'), ['sort-high', 'sort-mid', 'sort-low']));

    expect($asc)->toBe(['sort-low', 'sort-mid', 'sort-high'])
        ->and($desc)->toBe(['sort-high', 'sort-mid', 'sort-low']);
});

it('does not break the grid when a numeric attribute holds text or an empty value', function () {
    $code = 'sort_bad_'.Str::lower(Str::random(6));

    Attribute::factory()->validation_decimal()->create(['code' => $code, 'type' => 'text']);

    foreach (['sort-bad-text' => 'abc', 'sort-bad-empty' => '', 'sort-bad-num' => '2.5'] as $sku => $value) {
        setCommonValue(Product::factory()->simple()->create(['sku' => $sku]), $code, $value);
    }

    expect(gridSkusSortedBy($code, 'asc'))->toContain('sort-bad-text', 'sort-bad-empty', 'sort-bad-num');
});

it('sorts a variant by the value it inherits from its parent', function () {
    $code = 'sort_inh_'.Str::lower(Str::random(6));

    Attribute::factory()->validation_decimal()->create(['code' => $code, 'type' => 'text']);

    $parent = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'sort-inh-parent']);
    $variant = Product::where('parent_id', $parent->id)->firstOrFail();
    $variant->update(['sku' => 'sort-inh-variant']);

    $small = Product::factory()->simple()->create(['sku' => 'sort-inh-small']);
    $large = Product::factory()->simple()->create(['sku' => 'sort-inh-large']);

    setCommonValue($parent, $code, '2.15');
    setCommonValue($variant, $code, null);
    setCommonValue($small, $code, '2.10');
    setCommonValue($large, $code, '2.20');

    $skus = array_values(array_intersect(
        gridSkusSortedBy($code, 'asc'),
        [$small->sku, $variant->sku, $large->sku]
    ));

    expect($skus)->toBe([$small->sku, $variant->sku, $large->sku]);
});
