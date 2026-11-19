<?php

use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

afterEach(function () {
    ProductPrompt::resetInstance();
});

dataset('magic ai product read routes', [
    'is translatable'                => 'admin.magic_ai.check.is_translatable',
    'translate to many locales'      => 'admin.magic_ai.translate',
    'is all attribute translatable'  => 'admin.magic_ai.check.is_all_attribute_translatable',
    'translate all attributes'       => 'admin.magic_ai.translate.all.attribute',
]);

it('denies reading product values through magic ai without the product view permission', function (string $route) {
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

it('lets a user with the product view permission past the magic ai read guard', function (string $route) {
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
