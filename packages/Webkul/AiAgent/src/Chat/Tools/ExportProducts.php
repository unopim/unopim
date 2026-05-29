<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;

class ExportProducts implements PimTool
{
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
        $outer = $this;

        return new class($context, $outer) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected ExportProducts $outer)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'export_products';
            }

            public function description(): string
            {
                return 'Export products to CSV or XLSX. Returns a download URL. Default format is CSV; use XLSX when the user explicitly asks for Excel or XLSX.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'skus'     => $schema->string()->description('Comma-separated SKUs to export (leave empty for all)'),
                    'status'   => $schema->string()->enum(['active', 'inactive', 'all'])->description('Filter by status'),
                    'category' => $schema->string()->description('Filter by category code'),
                    'format'   => $schema->string()->enum(['csv', 'xlsx'])->description('Export file format'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'data_transfer.export')) {
                    return $denied;
                }

                $skus = $request->string('skus')->toString() ?: null;
                $status = $request->string('status')->toString() ?: 'all';
                $category = $request->string('category')->toString() ?: null;
                $format = $request->string('format')->toString() ?: 'csv';

                $format = strtolower($format);

                $filters = $this->outer->buildFilters($this->context, $skus, $status, $category, $format);
                $jobInstance = $this->outer->createJobInstance($filters);
                $jobTrack = $this->outer->createJobTrack($jobInstance);
                $logger = JobLogger::make($jobTrack->id);

                $logger->info('AI export request received.');

                try {
                    $products = $this->outer->collectProducts($filters);

                    if ($products->isEmpty()) {
                        $message = $category
                            ? "No products found in category: {$category}"
                            : 'No products match the criteria.';

                        $this->outer->markTrackAsFailed($jobTrack->id, $message, $logger);

                        return json_encode(['error' => $message]);
                    }

                    $extension = $format === 'xlsx' ? 'xlsx' : 'csv';
                    $filename = sprintf('export-%s.%s', now()->format('Y-m-d-His'), $extension);
                    $relativePath = 'ai-agent/exports/'.$filename;

                    $this->outer->markTrackAsProcessing($jobTrack->id);

                    $rows = $this->outer->buildCsvRows($products, $this->context);

                    if ($format === 'xlsx') {
                        $this->outer->writeXlsx($relativePath, $rows);
                    } else {
                        $this->outer->writeCsv($relativePath, $rows);
                    }

                    $this->outer->markTrackAsCompleted($jobTrack->id, $products, $relativePath, $logger);

                    return json_encode([
                        'result' => [
                            'exported'   => $products->count(),
                            'filename'   => $filename,
                            'format'     => $extension,
                            'tracker_id' => $jobTrack->id,
                        ],
                        'download_url' => asset('storage/'.$relativePath),
                    ]);
                } catch (\Throwable $exception) {
                    $this->outer->markTrackAsFailed($jobTrack->id, $exception->getMessage(), $logger);

                    report($exception);

                    return json_encode([
                        'error' => 'Failed to export products. Please try again.',
                    ]);
                }
            }
        };
    }

    /**
     * Build the export filters stored against the job instance.
     */
    public function buildFilters(ChatContext $context, ?string $skus, string $status, ?string $category, string $format): array
    {
        $skuList = array_values(array_filter(array_map(trim(...), explode(',', (string) $skus))));

        return [
            'file_format' => $format === 'xlsx' ? 'Xlsx' : 'Csv',
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
    public function createJobInstance(array $filters): mixed
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
    public function createJobTrack(mixed $jobInstance): mixed
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
    public function collectProducts(array $filters): Collection
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

        return $products->filter(function (\stdClass $product) use ($filters) {
            $values = json_decode((string) $product->values, true) ?? [];

            return in_array($filters['category'], $values['categories'] ?? [], true);
        })->values();
    }

    /**
     * Convert the selected products into export rows.
     */
    public function buildCsvRows(Collection $products, ChatContext $context): array
    {
        $rows = [
            ['sku', 'name', 'type', 'status', 'description', 'price', 'categories'],
        ];

        foreach ($products as $product) {
            $values = json_decode((string) $product->values, true) ?? [];
            $channelLocaleValues = $values['channel_locale_specific'][$context->channel][$context->locale] ?? [];
            $commonValues = $values['common'] ?? [];

            $price = '';

            if (isset($channelLocaleValues['price']) && is_array($channelLocaleValues['price'])) {
                $price = implode(', ', array_map(
                    fn (mixed $currency, mixed $amount) => "{$currency}: {$amount}",
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
    public function writeCsv(string $relativePath, array $rows): void
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
     * Write the generated XLSX to the public storage disk.
     */
    public function writeXlsx(string $relativePath, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }

        $fullPath = Storage::disk('public')->path($relativePath);
        $dir = \dirname($fullPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
    }

    /**
     * Mark the tracker row as actively processing.
     */
    public function markTrackAsProcessing(int $jobTrackId): void
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
    public function markTrackAsCompleted(int $jobTrackId, Collection $products, string $relativePath, LoggerInterface $logger): void
    {
        $summary = [
            'processed' => $products->count(),
            'created'   => $products->count(),
            'skipped'   => 0,
        ];

        $this->jobTrackBatchRepository->create([
            'state'        => ExportHelper::STATE_PROCESSED,
            'data'         => $products->pluck('id')->map(fn (mixed $id) => ['id' => $id])->values()->all(),
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
    public function markTrackAsFailed(int $jobTrackId, string $message, LoggerInterface $logger): void
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
