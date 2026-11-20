<?php

use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Build the agent system prompt for a custom role chatting from a product page.
 *
 * @param  array<int, string>  $permissions
 */
function productContextSystemPrompt(array $permissions, int $productId): string
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    $context = new ChatContext(
        message: 'Which product is this?',
        history: [],
        productId: $productId,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: Admin::factory()->create(['role_id' => $role->id]),
    );

    $runner = new AgentRunner(new ToolRegistry);

    return (new ReflectionMethod($runner, 'buildSystemPrompt'))->invoke($runner, $context);
}

it('keeps the product sku, type and status out of the prompt without the product view permission', function (): void {
    $product = Product::factory()->simple()->create();

    $prompt = productContextSystemPrompt(['dashboard', 'ai-agent'], $product->id);

    expect($prompt)->not->toContain('PRODUCT CONTEXT:')
        ->and($prompt)->not->toContain($product->sku);
});

it('adds the product context to the prompt for a user who can view products', function (): void {
    $product = Product::factory()->simple()->create();

    $prompt = productContextSystemPrompt(['dashboard', 'ai-agent', 'catalog', 'catalog.products'], $product->id);

    expect($prompt)->toContain('PRODUCT CONTEXT: SKU='.$product->sku);
});
