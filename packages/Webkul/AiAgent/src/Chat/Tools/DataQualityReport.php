<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class DataQualityReport implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('data_quality_report')
            ->for('Generate a catalog data quality report. Scans for incomplete products, missing descriptions, missing images, products without categories, and missing translations.')
            ->withStringParameter('category', 'Optional: limit scan to a specific category code')
            ->withNumberParameter('limit', 'Maximum products to scan (default 200, max 1000)')
            ->using(function (?string $category = null, int $limit = 200) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products')) {
                    return $denied;
                }

                $limit = min(max($limit, 1), 1000);
                $channel = $context->channel;
                $locale = $context->locale;

                $qb = DB::table('products')
                    ->select('id', 'sku', 'status', 'values')
                    ->limit($limit);

                if ($category) {
                    $qb->whereRaw("JSON_CONTAINS(JSON_EXTRACT(`values`, '$.categories'), ?)", ['"'.$category.'"']);
                }

                $products = $qb->get();

                $missingName = [];
                $missingDescription = [];
                $missingImage = [];
                $missingCategory = [];
                $shortDescription = [];
                $inactive = [];

                foreach ($products as $p) {
                    $values = json_decode($p->values, true) ?? [];
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
                    } elseif (mb_strlen($desc) < 50) {
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
            });
    }
}
