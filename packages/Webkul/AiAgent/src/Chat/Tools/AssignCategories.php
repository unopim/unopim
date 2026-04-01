<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class AssignCategories implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('assign_categories')
            ->for('Assign categories to products by SKU.')
            ->withStringParameter('skus', 'Comma-separated product SKUs')
            ->withStringParameter('categories', 'Comma-separated category codes or paths')
            ->using(function (string $skus, string $categories) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                $skuList = array_map('trim', explode(',', $skus));
                $categoryInputs = array_map('trim', explode(',', $categories));

                // Build candidate codes to search for
                $candidates = [];

                foreach ($categoryInputs as $input) {
                    $candidates[] = $input;
                    $segments = array_map('trim', explode('>', $input));
                    $last = end($segments);
                    $candidates[] = $last;
                    $candidates[] = Str::slug($last);
                }

                $candidates = array_unique(array_filter($candidates));

                $resolvedCodes = DB::table('categories')
                    ->whereIn('code', $candidates)
                    ->pluck('code')
                    ->toArray();

                if (empty($resolvedCodes)) {
                    return json_encode(['error' => 'No matching categories found for: '.implode(', ', $categoryInputs)]);
                }

                $updated = 0;
                $errors = [];
                $repo = app('Webkul\Product\Repositories\ProductRepository');

                foreach ($skuList as $sku) {
                    $product = $repo->findOneByField('sku', $sku);

                    if (! $product) {
                        $errors[] = "SKU not found: {$sku}";

                        continue;
                    }

                    $values = $product->values ?? [];
                    $existing = $values['categories'] ?? [];

                    if (! is_array($existing)) {
                        $existing = [];
                    }

                    $values['categories'] = array_values(array_unique(array_merge($existing, $resolvedCodes)));

                    $repo->updateWithValues(['values' => $values], $product->id);
                    $updated++;
                }

                return json_encode([
                    'result' => [
                        'updated'    => $updated,
                        'skus'       => implode(', ', $skuList),
                        'categories' => implode(', ', $resolvedCodes),
                        'errors'     => empty($errors) ? null : $errors,
                    ],
                ]);
            });
    }
}
