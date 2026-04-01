<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageAssociations implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_associations')
            ->for('Add product associations (related, up-sell, cross-sell). Defaults to append mode which keeps existing associations.')
            ->withStringParameter('sku', 'The product SKU to update associations for')
            ->withStringParameter('related', 'Comma-separated SKUs for related products (leave empty to skip)')
            ->withStringParameter('up_sells', 'Comma-separated SKUs for up-sell products (leave empty to skip)')
            ->withStringParameter('cross_sells', 'Comma-separated SKUs for cross-sell products (leave empty to skip)')
            ->withEnumParameter('mode', 'append (default) keeps existing and adds new; replace removes all existing first. Use append unless user explicitly asks to replace.', ['append', 'replace'])
            ->using(function (string $sku, ?string $related = null, ?string $up_sells = null, ?string $cross_sells = null, string $mode = 'append') use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                $productRepo = app('Webkul\Product\Repositories\ProductRepository');
                $product = $productRepo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "SKU not found: {$sku}"]);
                }

                $inputs = [
                    'related_products' => $related,
                    'up_sells'         => $up_sells,
                    'cross_sells'      => $cross_sells,
                ];

                $resolvedAssociations = [];
                $errors = [];

                foreach ($inputs as $type => $skuString) {
                    if (empty($skuString)) {
                        continue;
                    }

                    $requestedSkus = array_filter(array_map('trim', explode(',', $skuString)));

                    // Validate that all SKUs exist and are not self-referencing
                    $validSkus = DB::table('products')
                        ->whereIn('sku', $requestedSkus)
                        ->where('sku', '!=', $sku)
                        ->pluck('sku')
                        ->toArray();

                    $invalid = array_diff($requestedSkus, $validSkus);
                    if (! empty($invalid)) {
                        $errors[] = "{$type}: SKUs not found — ".implode(', ', $invalid);
                    }

                    if ($mode === 'append') {
                        $existing = $product->values['associations'][$type] ?? [];
                        $validSkus = array_values(array_unique(array_merge(
                            is_array($existing) ? $existing : [],
                            $validSkus,
                        )));
                    }

                    if (! empty($validSkus)) {
                        $resolvedAssociations[$type] = $validSkus;
                    }
                }

                if (empty($resolvedAssociations)) {
                    $msg = 'No valid associations to apply.';
                    if (! empty($errors)) {
                        $msg .= ' Errors: '.implode('; ', $errors);
                    }

                    return json_encode(['error' => $msg]);
                }

                // Write associations directly into the product values JSON
                $values = $product->values ?? [];
                $associations = $values['associations'] ?? [];

                foreach ($resolvedAssociations as $type => $skus) {
                    $associations[$type] = $skus;
                }

                $values['associations'] = $associations;
                $product->values = $values;
                $product->save();

                return json_encode([
                    'result' => [
                        'sku'          => $sku,
                        'associations' => $resolvedAssociations,
                        'mode'         => $mode,
                        'errors'       => empty($errors) ? null : $errors,
                    ],
                ]);
            });
    }
}
