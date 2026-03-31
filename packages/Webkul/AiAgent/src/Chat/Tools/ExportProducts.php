<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ExportProducts implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('export_products')
            ->for('Export products to CSV. Returns a download URL.')
            ->withStringParameter('skus', 'Comma-separated SKUs to export (leave empty for all)')
            ->withEnumParameter('status', 'Filter by status', ['active', 'inactive', 'all'])
            ->withStringParameter('category', 'Filter by category code')
            ->using(function (?string $skus = null, string $status = 'all', ?string $category = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'data_transfer.export')) {
                    return $denied;
                }

                $prefix = DB::getTablePrefix();

                $qb = DB::table('products as p')
                    ->select('p.id', 'p.sku', 'p.type', 'p.status', DB::raw("`{$prefix}p`.`values`"));

                if ($skus) {
                    $skuList = array_map('trim', explode(',', $skus));
                    $qb->whereIn('p.sku', $skuList);
                }

                if ($status !== 'all') {
                    $qb->where('p.status', $status === 'active' ? 1 : 0);
                }

                $products = $qb->orderBy('p.id')->limit(1000)->get();

                if ($products->isEmpty()) {
                    return json_encode(['error' => 'No products match the criteria.']);
                }

                // Filter by category if specified
                if ($category) {
                    $products = $products->filter(function ($p) use ($category) {
                        $values = json_decode($p->values, true) ?? [];
                        $cats = $values['categories'] ?? [];

                        return \in_array($category, $cats, true);
                    });

                    if ($products->isEmpty()) {
                        return json_encode(['error' => "No products found in category: {$category}"]);
                    }
                }

                // Build CSV
                $headers = ['sku', 'name', 'type', 'status', 'description', 'price', 'categories'];
                $rows = [$headers];

                foreach ($products as $p) {
                    $values = json_decode($p->values, true) ?? [];
                    $cl = $values['channel_locale_specific'][$context->channel][$context->locale] ?? [];
                    $common = $values['common'] ?? [];

                    $price = '';
                    if (isset($cl['price']) && is_array($cl['price'])) {
                        $price = implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($cl['price']), $cl['price']));
                    }

                    $rows[] = [
                        $p->sku,
                        $cl['name'] ?? $common['url_key'] ?? '',
                        $p->type,
                        $p->status ? 'active' : 'inactive',
                        substr($cl['description'] ?? '', 0, 200),
                        $price,
                        implode(', ', $values['categories'] ?? []),
                    ];
                }

                // Write CSV file
                $filename = 'export-'.date('Y-m-d-His').'.csv';
                $path = storage_path('app/public/ai-agent/exports/'.$filename);

                // Validate path stays within allowed directory.
                $baseDir = storage_path('app/public/ai-agent/');
                if (! str_starts_with($path, $baseDir)) {
                    return json_encode(['error' => trans('ai-agent::app.common.invalid-file-path')]);
                }

                if (! is_dir(\dirname($path))) {
                    mkdir(\dirname($path), 0755, true);
                }

                $fp = fopen($path, 'w');

                foreach ($rows as $row) {
                    fputcsv($fp, $row);
                }

                fclose($fp);

                $downloadUrl = asset('storage/ai-agent/exports/'.$filename);

                return json_encode([
                    'result' => [
                        'exported' => count($rows) - 1,
                        'filename' => $filename,
                    ],
                    'download_url' => $downloadUrl,
                ]);
            });
    }
}
