<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

function productWithAttribute(Attribute $attribute): Product
{
    $product = seedRequiredProductValues(Product::factory()->simple()->create());

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->syncWithoutDetaching([$attribute->id]);

    return $product;
}

function updateProductCommonValues(Product $product, array $values): void
{
    test()->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => $values],
    ])->assertSessionHasNoErrors()->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('keeps a select option whose code is zero', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => '0']);

    $product = productWithAttribute($attribute);

    updateProductCommonValues($product, [$attribute->code => '0']);

    expect($product->values['common'])->toHaveKey($attribute->code)
        ->and((string) $product->values['common'][$attribute->code])->toBe('0');
});

it('rejects a zero select value when no option has the code zero', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $product = productWithAttribute($attribute);

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attribute->code => '0']],
    ])->assertSessionHasErrors();

    expect($product->refresh()->values['common'])->not->toHaveKey($attribute->code);
});

it('keeps every multiselect option when one of them has the code zero', function () {
    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => '0']);

    $otherCode = $attribute->options()->where('code', '!=', '0')->value('code');

    $product = productWithAttribute($attribute);

    $product = app(ProductRepository::class)->update([
        'sku'    => $product->sku,
        'values' => ['common' => [$attribute->code => ['0', $otherCode]]],
    ], $product->id);

    expect($product->values['common'][$attribute->code] ?? null)->toBe('0,'.$otherCode);
});

it('keeps a zero value for text attributes', function (string $validation, mixed $value) {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => $validation]);

    $product = productWithAttribute($attribute);

    updateProductCommonValues($product, [$attribute->code => $value]);

    expect($product->values['common'])->toHaveKey($attribute->code)
        ->and((string) $product->values['common'][$attribute->code])->toBe('0');
})->with([
    'number' => ['number', 0],
    'text'   => ['', '0'],
]);

it('keeps a zero price and clears a blank currency over the stored price', function () {
    $attribute = Attribute::factory()->create(['type' => 'price']);

    $product = productWithAttribute($attribute);

    $zeroCurrency = core()->getDefaultChannel()->currencies->first()->code;

    $clearedCurrency = $zeroCurrency === 'EUR' ? 'USD' : 'EUR';

    $product->values = array_replace_recursive($product->values, [
        'common' => [$attribute->code => [$zeroCurrency => '5', $clearedCurrency => '7']],
    ]);

    $product->save();

    updateProductCommonValues($product, [$attribute->code => [$zeroCurrency => '0', $clearedCurrency => '']]);

    expect($product->values['common'][$attribute->code] ?? null)->toBe([$zeroCurrency => '0']);
});

it('still clears stored values posted as null or empty string', function () {
    $numberAttribute = Attribute::factory()->create(['type' => 'text', 'validation' => 'number']);

    $selectAttribute = Attribute::factory()->create(['type' => 'select']);

    $product = productWithAttribute($numberAttribute);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($selectAttribute);

    $product->values = array_replace_recursive($product->values, [
        'common' => [
            $numberAttribute->code => '5',
            $selectAttribute->code => $selectAttribute->options->first()->code,
        ],
    ]);

    $product->save();

    updateProductCommonValues($product, [
        $numberAttribute->code => '',
        $selectAttribute->code => null,
    ]);

    expect($product->values['common'])
        ->not->toHaveKey($numberAttribute->code)
        ->not->toHaveKey($selectAttribute->code);
});

it('rejects a zero value already stored on another product for a unique attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'is_unique' => 1]);

    updateProductCommonValues(productWithAttribute($attribute), [$attribute->code => '0']);

    $product = productWithAttribute($attribute);

    config(['elasticsearch.enabled' => false]);

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attribute->code => '0']],
    ])->assertInvalid('values[common]['.$attribute->code.']');

    expect($product->refresh()->values['common'])->not->toHaveKey($attribute->code);
});
