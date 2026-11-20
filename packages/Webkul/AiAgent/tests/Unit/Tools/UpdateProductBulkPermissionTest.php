<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\UpdateProduct;
use Webkul\Product\Models\Product;

/**
 * Run the update_product tool for the given SKUs and decode its JSON answer.
 *
 * @return array<string, mixed>
 */
function runUpdateProductTool(ChatContext $context, string $sku): array
{
    return json_decode(app(UpdateProduct::class)->register($context)->handle(new Request([
        'sku'          => $sku,
        'changes_json' => json_encode(['status' => 'inactive']),
    ])), true, 512, JSON_THROW_ON_ERROR);
}

it('denies a multi-sku update_product to a user who may edit but not bulk edit products', function (): void {
    $first = Product::factory()->simple()->create();
    $second = Product::factory()->simple()->create();

    $result = runUpdateProductTool(aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit']), $first->sku.','.$second->sku);

    expect($result['error'] ?? '')->toContain('catalog.products.bulk_edit');
});

it('lets a user who may edit products update a single sku', function (): void {
    $product = Product::factory()->simple()->create();

    $result = runUpdateProductTool(aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit']), $product->sku);

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});

it('lets a user who may edit and bulk edit products update several skus', function (): void {
    $first = Product::factory()->simple()->create();
    $second = Product::factory()->simple()->create();

    $result = runUpdateProductTool(
        aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit', 'catalog.products.bulk_edit']),
        $first->sku.','.$second->sku,
    );

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});
