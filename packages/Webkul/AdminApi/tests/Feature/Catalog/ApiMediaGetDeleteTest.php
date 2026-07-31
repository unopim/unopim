<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
    Storage::fake();
});

it('gets and deletes product media', function () {
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();
    $path = 'product/'.$product->id.'/'.$attribute->code.'/file.jpg';
    Storage::put($path, 'x');
    $product->values = array_merge($product->values ?? [], ['common' => [$attribute->code => $path]]);
    $product->save();

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.product.get', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertOk()
        ->assertJsonFragment(['data' => [$path]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertOk()
        ->assertJson(['success' => true]);

    Storage::assertMissing($path);
    expect($product->fresh()->values['common'][$attribute->code] ?? null)->toBeNull();
});

it('returns 404 deleting product media that does not exist', function () {
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertNotFound();
});

it('returns 404 for product media on an unknown sku', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.product.get', ['sku' => 'nope', 'attribute' => 'x']))
        ->assertNotFound();
});

it('gets and deletes swatch media', function () {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);
    $path = 'attribute_option/'.$option->id.'/swatch.png';
    Storage::put($path, 'x');
    $option->swatch_value = $path;
    $option->save();

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.attribute.options.get', ['attribute_code' => $attribute->code, 'code' => $option->code]))
        ->assertOk()
        ->assertJsonFragment(['data' => [$path]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.attribute.options.delete', ['attribute_code' => $attribute->code, 'code' => $option->code]))
        ->assertOk();

    Storage::assertMissing($path);
    expect($option->fresh()->swatch_value)->toBeNull();
});

it('deletes category media', function () {
    $field = CategoryField::factory()->create(['type' => 'image', 'value_per_locale' => 0]);
    $category = Category::factory()->create();
    $path = 'category/'.$category->id.'/'.$field->code.'/file.jpg';
    Storage::put($path, 'x');
    $category->additional_data = ['common' => [$field->code => $path]];
    $category->save();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.category.delete', ['code' => $category->code, 'category_field' => $field->code]))
        ->assertOk();

    Storage::assertMissing($path);
});

it('forbids product media delete without permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertForbidden();
});

it('rejects unauthenticated product media get', function () {
    $product = Product::factory()->create();

    $this->json('GET', route('admin.api.media-files.product.get', ['sku' => $product->sku, 'attribute' => 'x']), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
