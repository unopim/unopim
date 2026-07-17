<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Product\Repositories\ProductRepository;

class DeleteProducts implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'delete_products';
            }

            public function description(): string
            {
                return 'Delete products by SKU. Confirm with user first.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'skus'      => $schema->string()->description('Comma-separated list of product SKUs to delete'),
                    'confirmed' => $schema->boolean()->description('Must be true — indicates the user has confirmed deletion'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.delete')) {
                    return $denied;
                }

                $skus = $request->string('skus')->toString();
                $confirmed = $request->boolean('confirmed');

                if (! $confirmed) {
                    return json_encode(['error' => 'Deletion not confirmed. Ask the user to confirm before proceeding.']);
                }

                $skuList = array_map(trim(...), explode(',', $skus));

                if (count($skuList) > 20) {
                    return json_encode(['error' => trans('ai-agent::app.common.bulk-delete-limit')]);
                }

                $deleted = 0;
                $errors = [];

                $repo = resolve(ProductRepository::class);

                foreach ($skuList as $sku) {
                    $product = $repo->findOneByField('sku', $sku);

                    if (! $product) {
                        $errors[] = "SKU not found: {$sku}";

                        continue;
                    }

                    $repo->delete($product->id);
                    $deleted++;
                }

                return json_encode([
                    'result' => [
                        'deleted' => $deleted,
                        'skus'    => implode(', ', $skuList),
                        'errors'  => $errors === [] ? null : $errors,
                    ],
                ]);
            }
        };
    }
}
