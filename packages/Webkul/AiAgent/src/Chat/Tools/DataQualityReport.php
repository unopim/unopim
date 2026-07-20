<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class DataQualityReport implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'data_quality_report';
            }

            public function description(): string
            {
                return 'Generate a catalog data quality report. Scans for incomplete products, missing descriptions, missing images, products without categories, and missing translations.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'category' => $schema->string()->description('Optional: limit scan to a specific category code'),
                    'limit'    => $schema->integer()->description('Maximum products to scan (default 200, max 1000)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                $category = $request->string('category')->toString() ?: null;
                $limit = $request->integer('limit', 200);

                $limit = min(max($limit, 1), 1000);
                $channel = $this->context->channel;
                $locale = $this->context->locale;

                $grammar = GrammarQueryManager::getGrammar();

                $qb = DB::table('products')
                    ->select('id', 'sku', 'status', 'values')
                    ->limit($limit);

                if ($category) {
                    $qb->whereRaw($grammar->jsonContains('values', ['categories'], '?'), ['"'.$category.'"']);
                }

                $products = $qb->get();

                $missingName = [];
                $missingDescription = [];
                $missingImage = [];
                $missingCategory = [];
                $shortDescription = [];
                $inactive = [];

                foreach ($products as $p) {
                    $values = json_decode((string) $p->values, true) ?? [];
                    $common = $values['common'] ?? [];
                    $cl = $values['channel_locale_specific'][$channel][$locale] ?? [];
                    $cats = $values['categories'] ?? [];

                    $name = $cl['name'] ?? $common['name'] ?? null;
                    $desc = $cl['description'] ?? $common['description'] ?? null;

                    if (empty($name)) {
                        $missingName[] = $p->sku;
                    }

                    if (empty($desc)) {
                        $missingDescription[] = $p->sku;
                    } elseif (mb_strlen((string) $desc) < 50) {
                        $shortDescription[] = $p->sku;
                    }

                    if (empty($common['image'])) {
                        $missingImage[] = $p->sku;
                    }

                    if (empty($cats)) {
                        $missingCategory[] = $p->sku;
                    }

                    if (! $p->status) {
                        $inactive[] = $p->sku;
                    }
                }

                $totalScanned = $products->count();
                $issueCount = count($missingName) + count($missingDescription) + count($missingImage) + count($missingCategory);
                $healthScore = $totalScanned > 0
                    ? round((1 - ($issueCount / ($totalScanned * 4))) * 100)
                    : 100;

                return json_encode([
                    'result' => [
                        'scanned'              => $totalScanned,
                        'health_score'         => $healthScore,
                        'missing_name'         => ['count' => count($missingName), 'skus' => array_slice($missingName, 0, 10)],
                        'missing_description'  => ['count' => count($missingDescription), 'skus' => array_slice($missingDescription, 0, 10)],
                        'short_description'    => ['count' => count($shortDescription), 'skus' => array_slice($shortDescription, 0, 10)],
                        'missing_image'        => ['count' => count($missingImage), 'skus' => array_slice($missingImage, 0, 10)],
                        'missing_category'     => ['count' => count($missingCategory), 'skus' => array_slice($missingCategory, 0, 10)],
                        'inactive_products'    => ['count' => count($inactive)],
                        'locale'               => $locale,
                        'channel'              => $channel,
                    ],
                ]);
            }
        };
    }
}
