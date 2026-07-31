<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Product\Repositories\ProductRepository;

class AssignCategories implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'assign_categories';
            }

            public function description(): string
            {
                return 'Assign categories to products by SKU.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'skus'       => $schema->string()->description('Comma-separated product SKUs'),
                    'categories' => $schema->string()->description('Comma-separated category codes or paths'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $skus = $request->string('skus')->toString();
                $categories = $request->string('categories')->toString();

                $skuList = array_map(trim(...), explode(',', $skus));
                $categoryInputs = array_map(trim(...), explode(',', $categories));

                // Build candidate codes to search for
                $candidates = [];

                foreach ($categoryInputs as $input) {
                    $candidates[] = $input;
                    $segments = array_map(trim(...), explode('>', $input));
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
                $repo = resolve(ProductRepository::class);

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
                        'errors'     => $errors === [] ? null : $errors,
                    ],
                ]);
            }
        };
    }
}
