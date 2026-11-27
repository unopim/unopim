<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Product\Models\Product;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
    Storage::fake();
});

it('gets and deletes product media', function (): void {
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

it('returns 404 deleting product media that does not exist', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertNotFound();
});

it('returns 404 for product media on an unknown sku', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.product.get', ['sku' => 'nope', 'attribute' => $attribute->code]))
        ->assertNotFound();
});

it('refuses to read or delete media through an attribute that does not hold media', function (string $method, string $route): void {
    $attribute = Attribute::factory()->create(['type' => 'text', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();
    $path = 'category/1/logo.png';
    Storage::put($path, 'x');
    $product->values = array_merge($product->values ?? [], ['common' => [$attribute->code => $path]]);
    $product->save();

    $this->withHeaders($this->headers)
        ->json($method, route($route, ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attribute']);

    Storage::assertExists($path);
    expect($product->fresh()->values['common'][$attribute->code])->toBe($path);
})->with([
    'get'    => ['GET', 'admin.api.media-files.product.get'],
    'delete' => ['DELETE', 'admin.api.media-files.product.delete'],
]);

it('refuses to read or delete media of an attribute that does not exist', function (string $method, string $route): void {
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->json($method, route($route, ['sku' => $product->sku, 'attribute' => 'missing_media_attribute']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attribute']);
})->with([
    'get'    => ['GET', 'admin.api.media-files.product.get'],
    'delete' => ['DELETE', 'admin.api.media-files.product.delete'],
]);

it('refuses an upload to an attribute that does not hold media', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'text', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->post(route('admin.api.media-files.product.store'), [
            'sku'       => $product->sku,
            'attribute' => $attribute->code,
            'file'      => UploadedFile::fake()->image('first.png'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attribute']);

    expect($product->fresh()->values['common'][$attribute->code] ?? null)->toBeNull();
});

it('deletes only the files inside the product media folder and still clears the value', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'gallery', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $folder = 'product/'.$product->id.'/'.$attribute->code;
    $owned = $folder.'/'.str_repeat('a', 40).'/own.png';
    $foreign = [
        'category/1/logo.png',
        'product/'.$otherProduct->id.'/'.$attribute->code.'/other.png',
        $folder.'/../../../channel/logo.png',
        $folder.'/..\\..\\..\\swatch.png',
        '/'.$folder.'/leading.png',
    ];

    foreach ([$owned, 'category/1/logo.png', 'product/'.$otherProduct->id.'/'.$attribute->code.'/other.png', 'channel/logo.png'] as $path) {
        Storage::put($path, 'x');
    }

    $product->values = array_merge($product->values ?? [], ['common' => [$attribute->code => [$owned, ...$foreign]]]);
    $product->save();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertOk();

    Storage::assertMissing($owned);
    Storage::assertExists(['category/1/logo.png', 'product/'.$otherProduct->id.'/'.$attribute->code.'/other.png', 'channel/logo.png']);
    expect($product->fresh()->values['common'][$attribute->code] ?? null)->toBeNull();
});

it('gets and deletes swatch media', function (): void {
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

it('deletes category media', function (): void {
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

it('forbids product media delete without permission', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);
    $attribute = Attribute::factory()->create(['type' => 'image', 'value_per_channel' => 0, 'value_per_locale' => 0]);
    $product = Product::factory()->create();

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $product->sku, 'attribute' => $attribute->code]))
        ->assertForbidden();
});

it('rejects unauthenticated product media get', function (): void {
    $product = Product::factory()->create();

    $this->json('GET', route('admin.api.media-files.product.get', ['sku' => $product->sku, 'attribute' => 'x']), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});

it('deletes only the files inside the category field folder and still clears the value', function (): void {
    $field = CategoryField::factory()->create(['type' => 'image', 'value_per_locale' => 0]);
    $category = Category::factory()->create();
    $owned = 'category/'.$category->id.'/'.$field->code.'/own.jpg';
    $foreign = ['product/1/gallery/kept.png', 'category/'.($category->id + 1).'/'.$field->code.'/other.jpg', 'channel/logo.png'];
    $stored = [$owned, ...$foreign, 'category/'.$category->id.'/'.$field->code.'/../../../channel/logo.png'];

    foreach ([$owned, ...$foreign] as $path) {
        Storage::put($path, 'x');
    }

    $category->additional_data = ['common' => [$field->code => implode(',', $stored)]];
    $category->save();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.category.delete', ['code' => $category->code, 'category_field' => $field->code]))
        ->assertOk();

    Storage::assertMissing($owned);
    Storage::assertExists($foreign);
    expect($category->fresh()->additional_data['common'][$field->code] ?? null)->toBeNull();
});

it('refuses to read or delete category media through a field that does not hold media', function (string $method, string $route, string $type): void {
    $field = CategoryField::factory()->create(['type' => $type, 'value_per_locale' => 0]);
    $category = Category::factory()->create();
    $path = 'product/1/gallery/kept.png';
    Storage::put($path, 'x');
    $category->additional_data = ['common' => [$field->code => $path]];
    $category->save();

    $this->withHeaders($this->headers)
        ->json($method, route($route, ['code' => $category->code, 'category_field' => $field->code]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_field']);

    Storage::assertExists($path);
    expect($category->fresh()->additional_data['common'][$field->code])->toBe($path);
})->with([
    'get text field'      => ['GET', 'admin.api.media-files.category.get', 'text'],
    'delete text field'   => ['DELETE', 'admin.api.media-files.category.delete', 'text'],
    'delete select field' => ['DELETE', 'admin.api.media-files.category.delete', 'select'],
]);

it('refuses category media of a field that does not exist', function (): void {
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.category.delete', ['code' => $category->code, 'category_field' => 'missing_media_field']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_field']);
});

it('refuses a category upload to a field that does not hold media', function (): void {
    $field = CategoryField::factory()->create(['type' => 'text', 'value_per_locale' => 0]);
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->post(route('admin.api.media-files.category.store'), [
            'code'           => $category->code,
            'category_field' => $field->code,
            'file'           => UploadedFile::fake()->image('first.png'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_field']);
});

it('deletes a swatch stored by the admin form and leaves a swatch value pointing outside the swatch folder on disk', function (string $stored, bool $deleted): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);
    Storage::put('channel/logo.png', 'x');
    Storage::put($stored, 'x');
    $option->swatch_value = $stored;
    $option->save();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.attribute.options.delete', ['attribute_code' => $attribute->code, 'code' => $option->code]))
        ->assertOk();

    expect(Storage::exists($stored))->toBe(! $deleted)
        ->and($option->fresh()->swatch_value)->toBeNull();
    Storage::assertExists('channel/logo.png');
})->with([
    'admin form swatch'   => ['attribute_option/AbCdEf0123456789.png', true],
    'product image'       => ['product/1/gallery/kept.png', false],
    'path stepping out'   => ['attribute_option/../channel/logo.png', false],
]);

it('refuses a category media upload whose file name contains a comma', function (): void {
    $field = CategoryField::factory()->create(['type' => 'image', 'value_per_locale' => 0]);
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->post(route('admin.api.media-files.category.store'), [
            'code'           => $category->code,
            'category_field' => $field->code,
            'file'           => UploadedFile::fake()->image('red,blue.png'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);

    expect(Storage::allFiles('category/'.$category->id))->toBe([]);
});

it('refuses a swatch upload whose file name contains a comma', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $this->withHeaders($this->headers)
        ->post(route('admin.api.media-files.attribute.options.store'), [
            'attribute_code' => $attribute->code,
            'code'           => $option->code,
            'file'           => UploadedFile::fake()->image('red,blue.png'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);

    expect($option->fresh()->swatch_value)->toBeNull();
});
