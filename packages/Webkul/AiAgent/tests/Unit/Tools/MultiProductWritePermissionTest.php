<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\AssignCategories;
use Webkul\AiAgent\Chat\Tools\DeleteProducts;
use Webkul\Product\Models\Product;

/**
 * Run a product tool with the given arguments and decode its JSON answer.
 *
 * @param  class-string  $tool
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function runMultiProductTool(string $tool, ChatContext $context, array $arguments): array
{
    return json_decode(app($tool)->register($context)->handle(new Request($arguments)), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Create two simple products and return their SKUs as a comma-separated list.
 */
function twoProductSkus(): string
{
    return Product::factory()->simple()->count(2)->create()->pluck('sku')->implode(',');
}

it('denies a multi-sku assign_categories to a user who may edit but not bulk edit products', function (): void {
    $result = runMultiProductTool(AssignCategories::class, aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit']), [
        'skus'       => twoProductSkus(),
        'categories' => 'root',
    ]);

    expect($result['error'] ?? '')->toContain('catalog.products.bulk_edit');
});

it('lets a user who may edit products assign categories to a single sku', function (): void {
    $product = Product::factory()->simple()->create();

    $result = runMultiProductTool(AssignCategories::class, aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit']), [
        'skus'       => $product->sku,
        'categories' => 'root',
    ]);

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});

it('lets a user who may bulk edit products assign categories to several skus', function (): void {
    $result = runMultiProductTool(AssignCategories::class, aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.edit', 'catalog.products.bulk_edit']), [
        'skus'       => twoProductSkus(),
        'categories' => 'root',
    ]);

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});

it('denies a multi-sku delete_products to a user who may delete but not mass delete products', function (): void {
    $skus = twoProductSkus();

    $result = runMultiProductTool(DeleteProducts::class, aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.delete']), [
        'skus'      => $skus,
        'confirmed' => true,
    ]);

    expect($result['error'] ?? '')->toContain('catalog.products.mass_delete')
        ->and(Product::whereIn('sku', explode(',', $skus))->count())->toBe(2);
});

it('lets a user who may mass delete products delete several skus', function (): void {
    $skus = twoProductSkus();

    runMultiProductTool(DeleteProducts::class, aiAgentToolContext(['catalog', 'catalog.products', 'catalog.products.delete', 'catalog.products.mass_delete']), [
        'skus'      => $skus,
        'confirmed' => true,
    ]);

    expect(Product::whereIn('sku', explode(',', $skus))->count())->toBe(0);
});
