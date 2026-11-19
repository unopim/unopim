<?php

use Webkul\Category\Models\Category;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Responses\GeneratedContent;
use Webkul\MagicAI\Services\Prompt\CategoryPrompt;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

afterEach(function () {
    ProductPrompt::resetInstance();
    CategoryPrompt::resetInstance();
});

function fakeMagicAiContentPlatform(): void
{
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault', 'setPlatformId', 'setModel', 'setTemperature', 'setMaxTokens', 'setSystemPrompt', 'setPrompt')->andReturnSelf();
    $mock->shouldReceive('askResult')->andReturn(new GeneratedContent('Generated content.'));

    app()->instance('magic_ai', $mock);
}

it('denies expanding a product into the content prompt without the product view permission', function () {
    fakeMagicAiContentPlatform();

    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'         => 'gpt-4o-mini',
        'prompt'        => 'Repeat @sku verbatim.',
        'resource_id'   => $product->id,
        'resource_type' => 'product',
    ])->assertForbidden();
});

it('denies expanding a category into the content prompt without the category view permission', function () {
    fakeMagicAiContentPlatform();

    $category = Category::factory()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'         => 'gpt-4o-mini',
        'prompt'        => 'Repeat @name verbatim.',
        'resource_id'   => $category->id,
        'resource_type' => 'category',
    ])->assertForbidden();
});

it('expands a product into the content prompt with the product view permission', function () {
    fakeMagicAiContentPlatform();

    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'         => 'gpt-4o-mini',
        'prompt'        => 'Describe @sku.',
        'resource_id'   => $product->id,
        'resource_type' => 'product',
    ])->assertOk()->assertJsonPath('content', 'Generated content.');
});

it('expands a category into the content prompt with the category view permission', function () {
    fakeMagicAiContentPlatform();

    $category = Category::factory()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.categories']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'         => 'gpt-4o-mini',
        'prompt'        => 'Describe @name.',
        'resource_id'   => $category->id,
        'resource_type' => 'category',
    ])->assertOk()->assertJsonPath('content', 'Generated content.');
});

it('generates content without a resource using only the ai-agent permission', function () {
    fakeMagicAiContentPlatform();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe a product.',
    ])->assertOk();
});
