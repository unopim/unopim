<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Product\Repositories\ProductRepository;

class ManageAssociations implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'manage_associations';
            }

            public function description(): string
            {
                return 'Add product associations (related, up-sell, cross-sell). Defaults to append mode which keeps existing associations.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'         => $schema->string()->description('The product SKU to update associations for'),
                    'related'     => $schema->string()->description('Comma-separated SKUs for related products (leave empty to skip)'),
                    'up_sells'    => $schema->string()->description('Comma-separated SKUs for up-sell products (leave empty to skip)'),
                    'cross_sells' => $schema->string()->description('Comma-separated SKUs for cross-sell products (leave empty to skip)'),
                    'mode'        => $schema->string()->enum(['append', 'replace'])->description('append (default) keeps existing and adds new; replace removes all existing first. Use append unless user explicitly asks to replace.'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString();
                $related = $request->string('related')->toString() ?: null;
                $up_sells = $request->string('up_sells')->toString() ?: null;
                $cross_sells = $request->string('cross_sells')->toString() ?: null;
                $mode = $request->string('mode')->toString() ?: 'append';

                $productRepo = resolve(ProductRepository::class);
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

                    $requestedSkus = array_filter(array_map(trim(...), explode(',', $skuString)));

                    // Validate that all SKUs exist and are not self-referencing
                    $validSkus = DB::table('products')
                        ->whereIn('sku', $requestedSkus)
                        ->where('sku', '!=', $sku)
                        ->pluck('sku')
                        ->toArray();

                    $invalid = array_diff($requestedSkus, $validSkus);
                    if ($invalid !== []) {
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
                        'errors'       => $errors === [] ? null : $errors,
                    ],
                ]);
            }
        };
    }
}
