<?php

use Webkul\Category\Models\Category;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Responses\GeneratedContent;
use Webkul\MagicAI\Services\Prompt\CategoryPrompt;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

afterEach(function (): void {
    ProductPrompt::resetInstance();
    CategoryPrompt::resetInstance();
});

/**
 * Bind a Magic AI double that returns fixed generated content.
 */
function fakeMagicAiContentPlatform(): void
{
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault', 'setPlatformId', 'setModel', 'setTemperature', 'setMaxTokens', 'setSystemPrompt', 'setPrompt')->andReturnSelf();
    $mock->shouldReceive('askResult')->andReturn(new GeneratedContent('Generated content.'));

    app()->instance('magic_ai', $mock);
}

it('denies expanding a product into the content prompt without the product view permission', function (): void {
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

it('denies expanding a category into the content prompt without the category view permission', function (): void {
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

it('expands a product into the content prompt with the product view permission', function (): void {
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

it('expands a category into the content prompt with the category view permission', function (): void {
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

it('generates content without a resource using only the ai-agent permission', function (): void {
    fakeMagicAiContentPlatform();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe a product.',
    ])->assertOk();
});

/**
 * Bind a Magic AI double that returns one fixed generated image.
 */
function fakeMagicAiImagePlatform(): void
{
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault', 'setPlatformId', 'setModel', 'setPrompt')->andReturnSelf();
    $mock->shouldReceive('images')->andReturn([['url' => 'data:image/png;base64,AAAA']]);

    app()->instance('magic_ai', $mock);
}

it('denies expanding a product into the image prompt without the product view permission', function (): void {
    fakeMagicAiImagePlatform();

    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route('admin.magic_ai.image'), [
        'model'         => 'dall-e-3',
        'prompt'        => 'Print @sku on the label.',
        'size'          => '1024x1024',
        'resource_id'   => $product->id,
        'resource_type' => 'product',
    ])->assertForbidden();
});

it('denies expanding a category into the image prompt without the category view permission', function (): void {
    fakeMagicAiImagePlatform();

    $category = Category::factory()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route('admin.magic_ai.image'), [
        'model'         => 'dall-e-3',
        'prompt'        => 'Print @name on the banner.',
        'size'          => '1024x1024',
        'resource_id'   => $category->id,
        'resource_type' => 'category',
    ])->assertForbidden();
});

it('expands a product into the image prompt with the product view permission', function (): void {
    fakeMagicAiImagePlatform();

    $product = Product::factory()->simple()->create();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products']);

    $this->postJson(route('admin.magic_ai.image'), [
        'model'         => 'dall-e-3',
        'prompt'        => 'Show @sku.',
        'size'          => '1024x1024',
        'resource_id'   => $product->id,
        'resource_type' => 'product',
    ])->assertOk()->assertJsonCount(1, 'images');
});

it('generates an image without a resource using only the ai-agent permission', function (): void {
    fakeMagicAiImagePlatform();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route('admin.magic_ai.image'), [
        'model'  => 'dall-e-3',
        'prompt' => 'A product on a white background.',
        'size'   => '1024x1024',
    ])->assertOk();
});
