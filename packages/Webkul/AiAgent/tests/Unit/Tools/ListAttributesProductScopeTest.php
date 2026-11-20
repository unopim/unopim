<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ListAttributes;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Build a chat context for a custom role, optionally scoped to a product being edited.
 *
 * @param  array<int, string>  $permissions
 */
function listAttributesToolContext(array $permissions, ?int $productId = null): ChatContext
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    return new ChatContext(
        message: 'List the attributes',
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
}

/**
 * Run the list_attributes tool and decode its JSON answer.
 *
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function runListAttributesTool(ChatContext $context, array $arguments = []): array
{
    return json_decode(app(ListAttributes::class)->register($context)->handle(new Request($arguments)), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Create a product in a family of its own, so its family is never the fallback one.
 */
function productInOwnFamily(): Product
{
    return Product::factory()->simple()->create([
        'attribute_family_id' => AttributeFamily::factory()->create()->id,
    ]);
}

it('denies resolving a family from a product sku without the product view permission', function (): void {
    $product = productInOwnFamily();

    $result = runListAttributesTool(listAttributesToolContext(['catalog', 'catalog.attributes']), ['sku' => $product->sku]);

    expect($result['error'] ?? '')->toContain('catalog.products')
        ->and($result)->not->toHaveKey('family_id');
});

it('does not resolve the family of the product being edited without the product view permission', function (): void {
    $product = productInOwnFamily();

    $result = runListAttributesTool(listAttributesToolContext(['catalog', 'catalog.attributes'], $product->id));

    expect($result['family_id'])->not->toBe($product->attribute_family_id);
});

it('resolves the family from a product sku or the edited product with the product view permission', function (): void {
    $product = productInOwnFamily();

    $permissions = ['catalog', 'catalog.attributes', 'catalog.products'];

    expect(runListAttributesTool(listAttributesToolContext($permissions), ['sku' => $product->sku])['family_id'])
        ->toBe($product->attribute_family_id)
        ->and(runListAttributesTool(listAttributesToolContext($permissions, $product->id))['family_id'])
        ->toBe($product->attribute_family_id);
});
