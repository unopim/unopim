<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;

class FindSimilarProducts implements PimTool
{
    use ChecksPermission;

    public function __construct(
        protected EmbeddingSimilarityService $embeddingSimilarityService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('find_similar_products')
            ->for('Find similar products using AI embeddings.')
            ->withStringParameter('query', 'Semantic query text for similarity search')
            ->withStringParameter('sku', 'Existing product SKU to find similar items for')
            ->withNumberParameter('limit', 'Maximum similar products to return (default 10, max 30)')
            ->using(function (?string $query = null, ?string $sku = null, int $limit = 10) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products')) {
                    return $denied;
                }

                $limit = min(max($limit, 1), 30);
                $poolLimit = 150;

                $sourceProduct = null;

                if (! empty($sku)) {
                    $sourceProduct = DB::table('products')
                        ->select('id', 'sku', 'type', 'values')
                        ->where('sku', $sku)
                        ->first();

                    if (! $sourceProduct) {
                        return json_encode(['error' => "SKU not found: {$sku}"]);
                    }
                }

                $queryText = trim((string) $query);

                if ($queryText === '' && $sourceProduct) {
                    $sourceValues = json_decode($sourceProduct->values, true) ?? [];
                    $sourceName = $sourceValues['channel_locale_specific'][$context->channel][$context->locale]['name']
                        ?? $sourceValues['common']['url_key']
                        ?? $sourceProduct->sku;

                    $queryText = implode(' | ', [
                        $sourceProduct->sku,
                        $sourceName,
                        $sourceProduct->type,
                    ]);
                }

                if ($queryText === '') {
                    return json_encode(['error' => 'Either query or sku is required.']);
                }

                $prefix = DB::getTablePrefix();

                $qb = DB::table('products as p')
                    ->leftJoin('attribute_families as af', 'af.id', '=', 'p.attribute_family_id')
                    ->select('p.id', 'p.sku', 'p.type', 'p.status', DB::raw("`{$prefix}p`.`values`"), 'af.code as family_code')
                    ->orderByDesc('p.id')
                    ->limit($poolLimit);

                if ($sourceProduct) {
                    $qb->where('p.id', '!=', $sourceProduct->id);
                }

                $products = $qb->get();

                if ($products->isEmpty()) {
                    return json_encode(['total' => 0, 'products' => []]);
                }

                $editBaseUrl = route('admin.catalog.products.edit', ['id' => '__ID__']);

                $rows = $products->map(function ($p) use ($context, $editBaseUrl) {
                    $values = json_decode($p->values, true) ?? [];
                    $name = $values['channel_locale_specific'][$context->channel][$context->locale]['name']
                        ?? $values['common']['url_key']
                        ?? '(unnamed)';

                    return [
                        'id'       => $p->id,
                        'sku'      => $p->sku,
                        'name'     => $name,
                        'type'     => $p->type,
                        'status'   => $p->status ? 'active' : 'inactive',
                        'family'   => $p->family_code,
                        'edit_url' => str_replace('__ID__', (string) $p->id, $editBaseUrl),
                    ];
                })->values();

                $documents = $rows->map(fn ($item) => implode(' | ', [
                    $item['sku'],
                    $item['name'],
                    $item['type'],
                    (string) $item['family'],
                    $item['status'],
                ]))->all();

                $ranked = $this->embeddingSimilarityService->rank($queryText, $documents, $limit);

                if (empty($ranked)) {
                    return json_encode([
                        'total'    => 0,
                        'products' => [],
                        'info'     => 'Similarity scoring unavailable. Check Laravel AI embeddings configuration.',
                    ]);
                }

                $results = [];

                foreach ($ranked as $item) {
                    $index = $item['index'];

                    if (! isset($rows[$index])) {
                        continue;
                    }

                    $row = $rows[$index];
                    $row['similarity_score'] = $item['score'];
                    $results[] = $row;
                }

                return json_encode([
                    'total'    => count($results),
                    'products' => $results,
                    'query'    => $queryText,
                ]);
            });
    }
}
