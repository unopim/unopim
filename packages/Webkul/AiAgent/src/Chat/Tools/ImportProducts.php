<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;

/**
 * Import/update products from an uploaded CSV or XLSX file.
 *
 * Reads the first uploaded spreadsheet, maps columns to product attributes,
 * and creates or updates products row by row. Requires a "sku" column.
 */
class ImportProducts implements PimTool
{
    use ChecksPermission;

    /**
     * Maximum rows to process per import to prevent timeouts.
     */
    protected const MAX_ROWS = 200;

    /**
     * Detected CSV delimiter for the current import.
     */
    protected string $detectedDelimiter = ',';

    public function __construct(
        protected ProductWriterService $writerService,
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected JobTrackBatchRepository $jobTrackBatchRepository,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('import_products')
            ->for('Import or update products from an uploaded CSV/XLSX file. The file must have a "sku" column. Existing SKUs are updated, new SKUs are created. Call this when the user uploads a spreadsheet file.')
            ->withEnumParameter('mode', 'Import mode', ['create_or_update', 'update_only', 'create_only'])
            ->withStringParameter('family_code', 'Attribute family code to use for new products (default: first available)')
            ->using(function (
                string $mode = 'create_or_update',
                ?string $family_code = null,
            ) use ($context): string {
                if ($denied = $this->denyImportExecution($context, $mode)) {
                    return $denied;
                }

                if (! $context->hasFiles()) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-no-file')]);
                }

                $filePath = $context->uploadedFilePaths[0];
                $originalFileName = basename($filePath);

                if (! file_exists($filePath)) {
                    return json_encode(['error' => trans('ai-agent::app.common.invalid-file-path')]);
                }

                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $this->detectedDelimiter = ',';

                $rows = match ($ext) {
                    'csv'         => $this->parseCsv($filePath),
                    'xlsx', 'xls' => $this->parseXlsx($filePath),
                    default       => null,
                };

                if ($rows === null) {
                    return json_encode(['error' => 'Unsupported file format. Please upload a CSV or XLSX file.']);
                }

                if (empty($rows)) {
                    return json_encode(['error' => 'The file is empty or could not be parsed.']);
                }

                // Normalize headers to lowercase
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), array_keys($rows[0]));
                $skuIndex = array_search('sku', $headers, true);

                if ($skuIndex === false) {
                    return json_encode([
                        'error'   => 'CSV must have a "sku" column. Found columns: '.implode(', ', $headers),
                        'columns' => $headers,
                    ]);
                }

                // Resolve attribute family
                $familyId = $this->resolveFamilyId($family_code);

                if (! $familyId) {
                    return json_encode(['error' => "Attribute family '{$family_code}' not found."]);
                }

                $storedFilePath = $this->storeImportFile($filePath, $originalFileName);
                $jobInstance = $this->createJobInstance(
                    $storedFilePath,
                    $mode,
                    $family_code,
                    $context,
                );
                $jobTrack = $this->createJobTrack($jobInstance);
                $logger = JobLogger::make($jobTrack->id);

                $logger->info('AI import request received.');

                $familyAttrs = $this->writerService->getFamilyAttributesPublic($familyId);
                $currencies = DB::table('currencies')->where('status', 1)->pluck('code')->toArray() ?: ['USD'];
                $repo = app('Webkul\Product\Repositories\ProductRepository');

                $created = 0;
                $updated = 0;
                $skipped = 0;
                $errors = [];
                $processedRows = 0;

                try {
                    $this->markTrackAsProcessing($jobTrack->id);

                    foreach ($rows as $i => $row) {
                        if ($processedRows >= self::MAX_ROWS) {
                            break;
                        }

                        $processedRows++;

                        // Re-key with lowercase headers
                        $normalizedRow = [];
                        foreach ($row as $key => $value) {
                            $normalizedRow[strtolower(trim((string) $key))] = $value;
                        }

                        $sku = trim((string) ($normalizedRow['sku'] ?? ''));

                        if (empty($sku)) {
                            $skipped++;

                            continue;
                        }

                        try {
                            $existingProduct = DB::table('products')->where('sku', $sku)->first();

                            if ($existingProduct && $mode === 'create_only') {
                                $skipped++;

                                continue;
                            }

                            if (! $existingProduct && $mode === 'update_only') {
                                $skipped++;

                                continue;
                            }

                            if ($existingProduct && ! $context->hasPermission('catalog.products.edit')) {
                                $skipped++;
                                $errors[] = 'Row '.($i + 2)." (SKU: {$sku}): Permission denied: you do not have 'catalog.products.edit' access.";

                                continue;
                            }

                            if (! $existingProduct && ! $context->hasPermission('catalog.products.create')) {
                                $skipped++;
                                $errors[] = 'Row '.($i + 2)." (SKU: {$sku}): Permission denied: you do not have 'catalog.products.create' access.";

                                continue;
                            }

                            if ($existingProduct) {
                                $this->updateProduct($existingProduct, $normalizedRow, $familyAttrs, $currencies, $context, $repo);
                                $updated++;
                            } else {
                                $this->createProduct($sku, $normalizedRow, $familyId, $familyAttrs, $currencies, $context, $repo);
                                $created++;
                            }
                        } catch (\Throwable $e) {
                            $errors[] = 'Row '.($i + 2)." (SKU: {$sku}): {$e->getMessage()}";
                        }
                    }

                    $result = [
                        'total_rows' => count($rows),
                        'processed'  => $processedRows,
                        'created'    => $created,
                        'updated'    => $updated,
                        'skipped'    => $skipped,
                        'errors'     => empty($errors) ? null : array_slice($errors, 0, 10),
                        'tracker_id' => $jobTrack->id,
                    ];

                    if ($processedRows < count($rows)) {
                        $result['warning'] = 'Only the first '.self::MAX_ROWS.' rows were processed. Remaining rows were skipped.';
                    }

                    $this->markTrackAsCompleted(
                        $jobTrack->id,
                        $processedRows,
                        $created,
                        $updated,
                        $errors,
                        $logger
                    );

                    return json_encode(['result' => $result]);
                } catch (\Throwable $exception) {
                    $this->markTrackAsFailed($jobTrack->id, $exception->getMessage(), $logger);

                    report($exception);

                    return json_encode([
                        'error' => 'Failed to import products. Please try again.',
                    ]);
                }
            });
    }

    /**
     * Store the uploaded import file on the private disk for history/tracker access.
     */
    protected function storeImportFile(string $filePath, string $originalFileName): string
    {
        return Storage::disk('private')->putFileAs(
            'imports',
            new File($filePath),
            time().'-'.$originalFileName
        );
    }

    /**
     * Create a job instance so the AI import appears in import history.
     */
    protected function createJobInstance(
        string $storedFilePath,
        string $mode,
        ?string $familyCode,
        ChatContext $context,
    ): mixed {
        return $this->jobInstancesRepository->create([
            'code'                => 'ai-agent-import-'.Str::lower(Str::random(10)),
            'entity_type'         => 'products',
            'type'                => 'import',
            'action'              => ImportHelper::ACTION_APPEND,
            'validation_strategy' => ImportHelper::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => $this->detectedDelimiter,
            'file_path'           => $storedFilePath,
            'filters'             => [
                'mode'        => $mode,
                'family_code' => $familyCode,
                'channel'     => $context->channel,
                'locale'      => $context->locale,
            ],
        ]);
    }

    /**
     * Create the tracker row for the AI-driven import.
     */
    protected function createJobTrack(mixed $jobInstance): mixed
    {
        return $this->jobTrackRepository->create([
            'action'                => $jobInstance->action,
            'validation_strategy'   => $jobInstance->validation_strategy,
            'type'                  => 'import',
            'state'                 => ImportHelper::STATE_PENDING,
            'allowed_errors'        => $jobInstance->allowed_errors,
            'field_separator'       => $jobInstance->field_separator,
            'file_path'             => $jobInstance->file_path,
            'images_directory_path' => $jobInstance->images_directory_path,
            'meta'                  => $jobInstance->toJson(),
            'job_instances_id'      => $jobInstance->id,
            'user_id'               => auth()->guard('admin')->id(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    /**
     * Mark the tracker row as actively processing.
     */
    protected function markTrackAsProcessing(int $jobTrackId): void
    {
        $this->jobTrackRepository->update([
            'state'      => ImportHelper::STATE_PROCESSING,
            'started_at' => now(),
            'summary'    => [],
        ], $jobTrackId);
    }

    /**
     * Mark the tracker and its single batch as completed.
     */
    protected function markTrackAsCompleted(
        int $jobTrackId,
        int $processedRows,
        int $created,
        int $updated,
        array $errors,
        $logger
    ): void {
        $summary = [
            'created' => $created,
            'updated' => $updated,
            'deleted' => 0,
        ];

        $this->jobTrackBatchRepository->create([
            'state'        => ImportHelper::STATE_PROCESSED,
            'data'         => ['processed_rows' => $processedRows],
            'summary'      => $summary,
            'job_track_id' => $jobTrackId,
        ]);

        $this->jobTrackRepository->update([
            'state'                => ImportHelper::STATE_COMPLETED,
            'processed_rows_count' => $processedRows,
            'invalid_rows_count'   => count($errors),
            'errors_count'         => count($errors),
            'errors'               => empty($errors) ? null : array_slice($errors, 0, 10),
            'summary'              => $summary,
            'completed_at'         => now(),
        ], $jobTrackId);

        $logger->info(sprintf(
            'AI import completed successfully. Processed: %d, created: %d, updated: %d, errors: %d.',
            $processedRows,
            $created,
            $updated,
            count($errors)
        ));
    }

    /**
     * Mark the tracker row as failed.
     */
    protected function markTrackAsFailed(int $jobTrackId, string $message, $logger): void
    {
        $this->jobTrackRepository->update([
            'state'        => ImportHelper::STATE_FAILED,
            'errors_count' => 1,
            'errors'       => [$message],
            'completed_at' => now(),
        ], $jobTrackId);

        $logger->error($message);
    }

    /**
     * Parse a CSV file into an array of associative rows.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function parseCsv(string $filePath): ?array
    {
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            return null;
        }

        // Detect delimiter by reading the first line
        $firstLine = fgets($handle);
        rewind($handle);

        if ($firstLine === false) {
            fclose($handle);

            return null;
        }

        $delimiter = ',';
        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');
        $tabCount = substr_count($firstLine, "\t");

        if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
            $delimiter = ';';
        } elseif ($tabCount > $commaCount) {
            $delimiter = "\t";
        }

        $this->detectedDelimiter = $delimiter;

        $headers = fgetcsv($handle, 0, $delimiter);

        if (! $headers) {
            fclose($handle);

            return null;
        }

        // Strip BOM from first header
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);

            if ($row !== false) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse an XLSX file into an array of associative rows using PhpSpreadsheet if available.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function parseXlsx(string $filePath): ?array
    {
        if (! class_exists(IOFactory::class)) {
            return null;
        }

        try {
            $this->detectedDelimiter = ',';
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            if (empty($data) || count($data) < 2) {
                return null;
            }

            $headers = array_shift($data);
            $rows = [];

            foreach ($data as $rowData) {
                if (count($rowData) === count($headers)) {
                    $row = array_combine($headers, $rowData);
                    if ($row !== false) {
                        $rows[] = $row;
                    }
                }
            }

            return $rows;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Create a new product from a CSV row.
     */
    protected function createProduct(
        string $sku,
        array $row,
        int $familyId,
        array $familyAttrs,
        array $currencies,
        ChatContext $context,
        mixed $repo,
    ): void {
        $product = $repo->create([
            'sku'                 => $sku,
            'type'                => $row['type'] ?? 'simple',
            'attribute_family_id' => $familyId,
        ]);

        $values = $product->values ?? [];
        $values['common']['sku'] = $sku;
        $values['common']['url_key'] = Str::slug($row['name'] ?? $sku);

        if (isset($row['product_number'])) {
            $values['common']['product_number'] = $row['product_number'];
        } elseif (isset($familyAttrs['product_number'])) {
            $values['common']['product_number'] = $sku;
        }

        // Handle status
        if (isset($row['status'])) {
            $isActive = \in_array(strtolower((string) $row['status']), ['1', 'active', 'yes', 'on', 'enabled'], true);
            DB::table('products')->where('id', $product->id)->update(['status' => $isActive ? 1 : 0]);
        }

        // Handle categories
        if (! empty($row['categories'])) {
            $catCodes = array_map('trim', explode(',', (string) $row['categories']));
            $validCodes = DB::table('categories')->whereIn('code', $catCodes)->pluck('code')->toArray();

            if (! empty($validCodes)) {
                $values['categories'] = $validCodes;
            }
        }

        $this->applyAttributeValues($values, $row, $familyAttrs, $currencies, $context);

        $repo->updateWithValues(['values' => $values], $product->id);
    }

    /**
     * Update an existing product from a CSV row.
     */
    protected function updateProduct(
        object $existingProduct,
        array $row,
        array $familyAttrs,
        array $currencies,
        ChatContext $context,
        mixed $repo,
    ): void {
        $values = json_decode($existingProduct->values, true) ?? [];

        // Reload family attrs for this specific product's family
        $productFamilyAttrs = $this->writerService->getFamilyAttributesPublic($existingProduct->attribute_family_id);

        // Handle status
        if (isset($row['status'])) {
            $isActive = \in_array(strtolower((string) $row['status']), ['1', 'active', 'yes', 'on', 'enabled'], true);
            DB::table('products')->where('id', $existingProduct->id)->update(['status' => $isActive ? 1 : 0]);
        }

        // Handle categories
        if (! empty($row['categories'])) {
            $catCodes = array_map('trim', explode(',', (string) $row['categories']));
            $validCodes = DB::table('categories')->whereIn('code', $catCodes)->pluck('code')->toArray();

            if (! empty($validCodes)) {
                $values['categories'] = $validCodes;
            }
        }

        $this->applyAttributeValues($values, $row, $productFamilyAttrs, $currencies, $context);

        $repo->updateWithValues(['values' => $values], $existingProduct->id);
    }

    /**
     * Apply attribute values from a CSV row to the product values array.
     */
    protected function applyAttributeValues(
        array &$values,
        array $row,
        array $familyAttrs,
        array $currencies,
        ChatContext $context,
    ): void {
        $skipColumns = ['sku', 'type', 'status', 'categories', 'product_number'];

        foreach ($row as $column => $cellValue) {
            $column = strtolower(trim((string) $column));

            if (\in_array($column, $skipColumns, true) || $cellValue === null || $cellValue === '') {
                continue;
            }

            if (! isset($familyAttrs[$column])) {
                continue;
            }

            $meta = $familyAttrs[$column];
            $value = $cellValue;

            // Handle price attributes
            if ($meta['type'] === 'price' && is_numeric($value)) {
                $priceObj = [];
                foreach ($currencies as $c) {
                    $priceObj[$c] = (string) round((float) $value, 2);
                }
                $value = $priceObj;
            }

            // Handle boolean attributes
            if ($meta['type'] === 'boolean') {
                $value = \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
            }

            // Handle select/multiselect attributes
            if (\in_array($meta['type'], ['select', 'multiselect'], true) && is_string($value)) {
                $resolved = $this->writerService->resolveSelectValuePublic($column, $value, $meta['attribute_id']);
                if ($resolved === null) {
                    continue;
                }
                $value = $resolved;
            }

            // Route to correct value bucket
            if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                $values['channel_locale_specific'][$context->channel][$context->locale][$column] = $value;
            } elseif ($meta['value_per_channel']) {
                $values['channel_specific'][$context->channel][$column] = $value;
            } elseif ($meta['value_per_locale']) {
                $values['locale_specific'][$context->locale][$column] = $value;
            } else {
                $values['common'][$column] = $value;
            }
        }
    }

    /**
     * Resolve attribute family ID from code or return the default.
     */
    protected function resolveFamilyId(?string $familyCode): ?int
    {
        if ($familyCode) {
            $id = DB::table('attribute_families')->where('code', $familyCode)->value('id');

            if ($id) {
                return $id;
            }
        }

        return DB::table('attribute_families')->value('id');
    }

    /**
     * Ensure the current user can execute AI imports for the requested mode.
     */
    protected function denyImportExecution(ChatContext $context, string $mode): ?string
    {
        if ($denied = $this->denyUnlessAllowed($context, 'data_transfer.imports.execute')) {
            return $denied;
        }

        $canCreate = $context->hasPermission('catalog.products.create');
        $canEdit = $context->hasPermission('catalog.products.edit');

        return match ($mode) {
            'create_only' => $canCreate ? null : $this->formatPermissionDenied('catalog.products.create'),
            'update_only' => $canEdit ? null : $this->formatPermissionDenied('catalog.products.edit'),
            default       => ($canCreate || $canEdit) ? null : $this->formatPermissionDenied('catalog.products.create or catalog.products.edit'),
        };
    }

    /**
     * Build a consistent permission denied response for tool execution.
     */
    protected function formatPermissionDenied(string $permission): string
    {
        return json_encode([
            'error' => "Permission denied: you do not have '{$permission}' access. Contact your administrator.",
        ]);
    }
}
