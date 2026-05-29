<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\Product\Repositories\ProductRepository;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        protected int $jobTrackId,
        protected array $rows,
        protected string $mode,
        protected int $familyId,
        protected array $familyAttrs,
        protected array $currencies,
        protected string $channel,
        protected string $locale,
    ) {}

    public function handle(
        ProductWriterService $writerService,
        JobTrackRepository $jobTrackRepository,
        JobTrackBatchRepository $jobTrackBatchRepository,
    ): void {
        $logger = JobLogger::make($this->jobTrackId);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $processedRows = 0;
        $repo = app(ProductRepository::class);

        try {
            $jobTrackRepository->update([
                'state'      => ImportHelper::STATE_PROCESSING,
                'started_at' => now(),
                'summary'    => [],
            ], $this->jobTrackId);

            foreach ($this->rows as $i => $normalizedRow) {
                $processedRows++;
                $sku = trim((string) ($normalizedRow['sku'] ?? ''));

                if ($sku === '' || $sku === '0') {
                    $skipped++;

                    continue;
                }

                try {
                    $existingProduct = DB::table('products')->where('sku', $sku)->first();

                    if ($existingProduct && $this->mode === 'create_only') {
                        $skipped++;

                        continue;
                    }

                    if (! $existingProduct && $this->mode === 'update_only') {
                        $skipped++;

                        continue;
                    }

                    if ($existingProduct) {
                        $values = json_decode((string) $existingProduct->values, true) ?? [];
                        $productFamilyAttrs = $writerService->getFamilyAttributesPublic($existingProduct->attribute_family_id);
                        $this->handleStatus($normalizedRow, $existingProduct->id);
                        $this->handleCategories($values, $normalizedRow);
                        $this->applyAttributeValues($values, $normalizedRow, $productFamilyAttrs, $writerService);
                        $repo->updateWithValues(['values' => $values], $existingProduct->id);
                        $updated++;
                    } else {
                        $values = ['common' => ['sku' => $sku]];
                        $product = $repo->create([
                            'sku'                 => $sku,
                            'type'                => $normalizedRow['type'] ?? 'simple',
                            'attribute_family_id' => $this->familyId,
                        ]);

                        $values['common']['url_key'] = Str::slug($normalizedRow['name'] ?? $sku);

                        if (isset($normalizedRow['product_number'])) {
                            $values['common']['product_number'] = $normalizedRow['product_number'];
                        } elseif (isset($this->familyAttrs['product_number'])) {
                            $values['common']['product_number'] = $sku;
                        }

                        $this->handleStatus($normalizedRow, $product->id);
                        $this->handleCategories($values, $normalizedRow);
                        $this->applyAttributeValues($values, $normalizedRow, $this->familyAttrs, $writerService);
                        $repo->updateWithValues(['values' => $values], $product->id);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Row '.($i + 2)." (SKU: {$sku}): {$e->getMessage()}";
                }
            }

            $summary = [
                'created' => $created,
                'updated' => $updated,
                'deleted' => 0,
            ];

            $jobTrackBatchRepository->create([
                'state'        => ImportHelper::STATE_PROCESSED,
                'data'         => ['processed_rows' => $processedRows],
                'summary'      => $summary,
                'job_track_id' => $this->jobTrackId,
            ]);

            $jobTrackRepository->update([
                'state'                => ImportHelper::STATE_COMPLETED,
                'processed_rows_count' => $processedRows,
                'invalid_rows_count'   => count($errors),
                'errors_count'         => count($errors),
                'errors'               => $errors === [] ? null : array_slice($errors, 0, 10),
                'summary'              => $summary,
                'completed_at'         => now(),
            ], $this->jobTrackId);

            $logger->info(sprintf(
                'AI import completed. Processed: %d, created: %d, updated: %d, errors: %d.',
                $processedRows,
                $created,
                $updated,
                count($errors)
            ));
        } catch (\Throwable $exception) {
            $jobTrackRepository->update([
                'state'        => ImportHelper::STATE_FAILED,
                'errors_count' => 1,
                'errors'       => [$exception->getMessage()],
                'completed_at' => now(),
            ], $this->jobTrackId);

            $logger->error($exception->getMessage());

            report($exception);
        }
    }

    protected function handleStatus(array $row, int $productId): void
    {
        if (isset($row['status'])) {
            $isActive = \in_array(strtolower((string) $row['status']), ['1', 'active', 'yes', 'on', 'enabled'], true);
            DB::table('products')->where('id', $productId)->update(['status' => $isActive ? 1 : 0]);
        }
    }

    protected function handleCategories(array &$values, array $row): void
    {
        if (! empty($row['categories'])) {
            $catCodes = array_map(trim(...), explode(',', (string) $row['categories']));
            $validCodes = DB::table('categories')->whereIn('code', $catCodes)->pluck('code')->toArray();

            if (! empty($validCodes)) {
                $values['categories'] = $validCodes;
            }
        }
    }

    protected function applyAttributeValues(
        array &$values,
        array $row,
        array $familyAttrs,
        ProductWriterService $writerService,
    ): void {
        $skipColumns = ['sku', 'type', 'status', 'categories', 'product_number'];

        foreach ($row as $column => $cellValue) {
            if (\in_array($column, $skipColumns, true) || $cellValue === null || $cellValue === '') {
                continue;
            }

            if (! isset($familyAttrs[$column])) {
                continue;
            }

            $meta = $familyAttrs[$column];
            $value = $cellValue;

            if ($meta['type'] === 'price' && is_numeric($value)) {
                $priceObj = [];
                foreach ($this->currencies as $c) {
                    $priceObj[$c] = (string) round((float) $value, 2);
                }
                $value = $priceObj;
            }

            if ($meta['type'] === 'boolean') {
                $value = \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
            }

            if (\in_array($meta['type'], ['select', 'multiselect'], true) && is_string($value)) {
                $resolved = $writerService->resolveSelectValuePublic($column, $value, $meta['attribute_id']);
                if ($resolved === null) {
                    continue;
                }
                $value = $resolved;
            }

            if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                $values['channel_locale_specific'][$this->channel][$this->locale][$column] = $value;
            } elseif ($meta['value_per_channel']) {
                $values['channel_specific'][$this->channel][$column] = $value;
            } elseif ($meta['value_per_locale']) {
                $values['locale_specific'][$this->locale][$column] = $value;
            } else {
                $values['common'][$column] = $value;
            }
        }
    }
}
