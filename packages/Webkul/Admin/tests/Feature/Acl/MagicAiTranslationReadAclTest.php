<?php

use Webkul\Category\Models\Category;
use Webkul\MagicAI\Services\Prompt\CategoryPrompt;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

afterEach(function (): void {
    ProductPrompt::resetInstance();
    CategoryPrompt::resetInstance();
});

dataset('magic ai product read routes', [
    'is translatable'                => 'admin.magic_ai.check.is_translatable',
    'translate to many locales'      => 'admin.magic_ai.translate',
    'is all attribute translatable'  => 'admin.magic_ai.check.is_all_attribute_translatable',
    'translate all attributes'       => 'admin.magic_ai.translate.all.attribute',
]);

it('denies reading product values through magic ai without the product view permission', function (string $route): void {
    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route($route), [
        'resource_id'   => $product->id,
        'resource_type' => 'product',
        'field'         => 'name',
        'attributes'    => 'name',
        'targetLocale'  => 'fr_FR',
    ])->assertForbidden();
})->with('magic ai product read routes');

it('lets a user with the product view permission past the magic ai read guard', function (string $route): void {
    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $response = $this->postJson(route($route), [
        'resource_id'   => $product->id,
        'resource_type' => 'product',
        'field'         => 'name',
        'attributes'    => 'name',
        'targetLocale'  => 'fr_FR',
    ]);

    expect($response->status())->not->toBe(403);
})->with('magic ai product read routes');

dataset('magic ai resource translate routes', [
    'translate to many locales' => 'admin.magic_ai.translate',
    'translate all attributes'  => 'admin.magic_ai.translate.all.attribute',
]);

it('denies translating a category through magic ai without the category view permission', function (string $route): void {
    $category = Category::factory()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route($route), [
        'resource_id'   => $category->id,
        'resource_type' => 'category',
        'field'         => 'description',
        'attributes'    => 'description',
        'targetLocale'  => 'fr_FR',
    ])->assertForbidden();
})->with('magic ai resource translate routes');

it('lets a user with the category view permission translate a category through magic ai', function (): void {
    $category = Category::factory()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.categories']);

    $response = $this->postJson(route('admin.magic_ai.translate'), [
        'resource_id'   => $category->id,
        'resource_type' => 'category',
        'field'         => 'description',
        'targetLocale'  => 'fr_FR',
    ]);

    expect($response->status())->not->toBe(403);
});

it('rejects a resource type magic ai cannot translate', function (string $route): void {
    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products', 'catalog.categories']);

    $this->postJson(route($route), [
        'resource_id'   => $product->id,
        'resource_type' => 'attribute',
        'field'         => 'name',
        'attributes'    => 'name',
        'targetLocale'  => 'fr_FR',
    ])->assertUnprocessable()->assertJsonValidationErrors('resource_type');
})->with('magic ai resource translate routes');

dataset('magic ai translatable check routes', [
    'is translatable'               => 'admin.magic_ai.check.is_translatable',
    'is all attribute translatable' => 'admin.magic_ai.check.is_all_attribute_translatable',
]);

it('answers a translatable check for a product that does not exist with not found', function (string $route, array $payload): void {
    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route($route), [
        'resource_type' => 'product',
        'field'         => 'name',
        'attributes'    => 'name',
        ...$payload,
    ])->assertNotFound();
})->with('magic ai translatable check routes')->with([
    'unknown product id' => [['resource_id' => 999999999]],
    'missing product id' => [[]],
]);
