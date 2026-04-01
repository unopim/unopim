<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class GetProductDetails implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('get_product_details')
            ->for('Get full product details by SKU or ID.')
            ->withStringParameter('sku', 'Product SKU (preferred)')
            ->withNumberParameter('product_id', 'Product ID (alternative to SKU)')
            ->using(function (?string $sku = null, ?int $product_id = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products')) {
                    return $denied;
                }

                $product = null;

                if ($sku) {
                    $product = DB::table('products')->where('sku', $sku)->first();
                } elseif ($product_id) {
                    $product = DB::table('products')->where('id', $product_id)->first();
                }

                if (! $product) {
                    return json_encode(['error' => 'Product not found']);
                }

                $values = json_decode($product->values, true) ?? [];
                $family = DB::table('attribute_families')
                    ->where('id', $product->attribute_family_id)
                    ->value('code');

                return json_encode([
                    'id'     => $product->id,
                    'sku'    => $product->sku,
                    'type'   => $product->type,
                    'status' => $product->status ? 'active' : 'inactive',
                    'family' => $family,
                    'values' => $values,
                ]);
            });
    }
}
