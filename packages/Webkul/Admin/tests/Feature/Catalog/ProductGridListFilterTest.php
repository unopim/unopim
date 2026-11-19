<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();

    $this->attribute = Attribute::factory()->create([
        'type'              => 'select',
        'value_per_locale'  => false,
        'value_per_channel' => false,
        'is_filterable'     => 1,
    ]);

    $this->gridSkus = function (FilterOperators $operator, array $value): array {
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), [
                'pagination'     => ['page' => 1, 'per_page' => 50],
                'sort'           => ['column' => 'product_id', 'order' => 'desc'],
                'managedColumns' => [$this->attribute->code],
                'filters'        => [
                    $this->attribute->code => [['operator' => $operator->value, 'value' => $value]],
                ],
            ])->assertOk();

        return array_values(array_intersect(
            array_column($response->json('records'), 'sku'),
            ['GRID-RED', 'GRID-RED-DARK', 'GRID-BLUE', 'GRID-NONE'],
        ));
    };

    foreach (['GRID-RED' => 'red', 'GRID-RED-DARK' => 'red_dark', 'GRID-BLUE' => 'blue', 'GRID-NONE' => null] as $sku => $color) {
        $product = Product::factory()->simple()->create(['sku' => $sku]);

        DB::table('products')->where('id', $product->id)->update([
            'values' => json_encode(array_replace_recursive($product->values ?? [], [
                'common' => $color === null ? [] : [$this->attribute->code => $color],
            ])),
        ]);
    }
});

it('matches whole option codes only with IN in the product grid', function () {
    expect(($this->gridSkus)(FilterOperators::IN, ['red']))->toBe(['GRID-RED']);
});

it('excludes only exact matches with NOT IN in the product grid', function () {
    $skus = ($this->gridSkus)(FilterOperators::NOT_IN, ['red']);

    expect($skus)->toContain('GRID-RED-DARK', 'GRID-BLUE', 'GRID-NONE')->not->toContain('GRID-RED');
});
