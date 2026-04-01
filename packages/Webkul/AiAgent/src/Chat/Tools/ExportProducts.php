<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;

class ExportProducts implements PimTool
{
    use ChecksPermission;

    /**
     * Create a new tool instance.
     */
    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected JobTrackBatchRepository $jobTrackBatchRepository
    ) {}

    /**
     * Register the export tool.
     */
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

                $logger->info('AI export request received.');

                try {
                    $products = $this->collectProducts($filters);

                    if ($products->isEmpty()) {
                        $message = $category
                            ? "No products found in category: {$category}"
                            : 'No products match the criteria.';

                        $this->markTrackAsFailed($jobTrack->id, $message, $logger);

                        return json_encode(['error' => $message]);
                    }

                    $filename = sprintf('export-%s.csv', now()->format('Y-m-d-His'));
                    $relativePath = 'ai-agent/exports/'.$filename;

                    $this->markTrackAsProcessing($jobTrack->id);
                    $this->writeCsv($relativePath, $this->buildCsvRows($products, $context));
                    $this->markTrackAsCompleted($jobTrack->id, $products, $relativePath, $logger);

                    return json_encode([
                        'result' => [
                            'exported'   => $products->count(),
                            'filename'   => $filename,
                            'tracker_id' => $jobTrack->id,
                        ],
                        'download_url' => asset('storage/'.$relativePath),
                    ]);
                } catch (\Throwable $exception) {
                    $this->markTrackAsFailed($jobTrack->id, $exception->getMessage(), $logger);

                    report($exception);

                    return json_encode([
                        'error' => 'Failed to export products. Please try again.',
                    ]);
                }
            });
    }

    /**
     * Build the export filters stored against the job instance.
     */
    protected function buildFilters(ChatContext $context, ?string $skus, string $status, ?string $category): array
    {
        $skuList = array_values(array_filter(array_map('trim', explode(',', (string) $skus))));

        return [
            'file_format' => 'Csv',
            'with_media'  => 0,
            'status'      => $status,
            'skus'        => $skuList,
            'category'    => $category,
            'channel'     => $context->channel,
            'locale'      => $context->locale,
        ];
    }

    /**
     * Create a job instance so the export appears in export history.
     */
    protected function createJobInstance(array $filters): mixed
    {
        return $this->jobInstancesRepository->create([
            'code'                => 'ai-agent-export-'.Str::lower(Str::random(10)),
            'entity_type'         => 'products',
            'type'                => 'export',
            'action'              => 'fetch',
            'validation_strategy' => 'skip',
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => '',
            'filters'             => $filters,
        ]);
    }

    /**
     * Create the tracker row for the AI-driven export.
     */
    protected function createJobTrack(mixed $jobInstance): mixed
    {
        return $this->jobTrackRepository->create([
            'action'              => 'export',
            'validation_strategy' => 'skip',
            'type'                => 'export',
            'state'               => ExportHelper::STATE_PENDING,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => '',
            'meta'                => $jobInstance->toJson(),
            'job_instances_id'    => $jobInstance->id,
            'user_id'             => auth()->guard('admin')->id(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Collect products matching the requested filters.
     */
    protected function collectProducts(array $filters): Collection
    {
        $query = DB::table('products as p')
            ->select('p.id', 'p.sku', 'p.type', 'p.status', 'p.values');

        if (! empty($filters['skus'])) {
            $query->whereIn('p.sku', $filters['skus']);
        }

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('p.status', $filters['status'] === 'active' ? 1 : 0);
        }

        $products = $query
            ->orderBy('p.id')
            ->limit(1000)
            ->get();

        if (empty($filters['category'])) {
            return $products;
        }

        return $products->filter(function ($product) use ($filters) {
            $values = json_decode($product->values, true) ?? [];

            return in_array($filters['category'], $values['categories'] ?? [], true);
        })->values();
    }

    /**
     * Convert the selected products into CSV rows.
     */
    protected function buildCsvRows(Collection $products, ChatContext $context): array
    {
        $rows = [
            ['sku', 'name', 'type', 'status', 'description', 'price', 'categories'],
        ];

        foreach ($products as $product) {
            $values = json_decode($product->values, true) ?? [];
            $channelLocaleValues = $values['channel_locale_specific'][$context->channel][$context->locale] ?? [];
            $commonValues = $values['common'] ?? [];

            $price = '';

            if (isset($channelLocaleValues['price']) && is_array($channelLocaleValues['price'])) {
                $price = implode(', ', array_map(
                    fn ($currency, $amount) => "{$currency}: {$amount}",
                    array_keys($channelLocaleValues['price']),
                    $channelLocaleValues['price']
                ));
            }

            $rows[] = [
                $product->sku,
                $channelLocaleValues['name'] ?? $commonValues['url_key'] ?? '',
                $product->type,
                $product->status ? 'active' : 'inactive',
                Str::limit((string) ($channelLocaleValues['description'] ?? ''), 200, ''),
                $price,
                implode(', ', $values['categories'] ?? []),
            ];
        }

        return $rows;
    }

    /**
     * Write the generated CSV to the public storage disk.
     */
    protected function writeCsv(string $relativePath, array $rows): void
    {
        $stream = fopen('php://temp', 'w+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);

        Storage::disk('public')->put($relativePath, stream_get_contents($stream));

        fclose($stream);
    }

    /**
     * Mark the tracker row as actively processing.
     */
    protected function markTrackAsProcessing(int $jobTrackId): void
    {
        $this->jobTrackRepository->update([
            'state'      => ExportHelper::STATE_PROCESSING,
            'started_at' => now(),
            'summary'    => [],
        ], $jobTrackId);
    }

    /**
     * Mark the tracker and its single batch as completed.
     */
    protected function markTrackAsCompleted(int $jobTrackId, Collection $products, string $relativePath, $logger): void
    {
        $summary = [
            'processed' => $products->count(),
            'created'   => $products->count(),
            'skipped'   => 0,
        ];

        $this->jobTrackBatchRepository->create([
            'state'        => ExportHelper::STATE_PROCESSED,
            'data'         => $products->pluck('id')->map(fn ($id) => ['id' => $id])->values()->all(),
            'summary'      => $summary,
            'job_track_id' => $jobTrackId,
        ]);

        $this->jobTrackRepository->update([
            'state'                => ExportHelper::STATE_COMPLETED,
            'file_path'            => $relativePath,
            'summary'              => $summary,
            'processed_rows_count' => $products->count(),
            'completed_at'         => now(),
        ], $jobTrackId);

        $logger->info(sprintf('AI export completed successfully with %d products.', $products->count()));
    }

    /**
     * Mark the tracker row as failed so the issue is visible in job history.
     */
    protected function markTrackAsFailed(int $jobTrackId, string $message, $logger): void
    {
        $this->jobTrackRepository->update([
            'state'        => ExportHelper::STATE_FAILED,
            'errors_count' => 1,
            'errors'       => [$message],
            'completed_at' => now(),
        ], $jobTrackId);

        $logger->error($message);
    }
}
