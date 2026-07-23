<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\ProductImportCsvNormalizer;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;

/**
 * Import or update products from an uploaded CSV or XLSX file.
 *
 * The uploaded spreadsheet is normalized into the core product-CSV shape and
 * handed to the core DataTransfer batch importer (validate → chunk → queued
 * batches → bulk upsert → index → completeness). This reuses the pipeline's
 * chunking, resume/pause/cancel, and full error-report capture, so large
 * catalogs (tens of thousands of SKUs) import reliably instead of being
 * processed in a single monolithic in-memory job. Existing SKUs are updated,
 * new SKUs are created (create-or-update).
 */
class ImportProducts implements PimTool
{
    use ChecksPermission;

    /**
     * Upper bound on rows parsed in the web request before hand-off to the
     * queued importer. Bounds worst-case memory for a hostile/oversized
     * upload; larger catalogs should use the standard Data Transfer import,
     * which streams the file from disk.
     */
    public const MAX_IMPORT_ROWS = 100000;

    public function __construct(
        protected ProductWriterService $writerService,
        protected ProductImportCsvNormalizer $normalizer,
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $outer = $this;

        return new class($context, $outer) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected ImportProducts $outer)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'import_products';
            }

            public function description(): string
            {
                return 'Import or update products from an uploaded CSV/XLSX file. The file must have a "sku" column. Existing SKUs are updated, new SKUs are created. Runs through the batched background importer, so large files are processed reliably. Call this when the user uploads a spreadsheet file.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'family_code' => $schema->string()->description('Attribute family code to use for new products (default: first available)'),
                ];
            }

            public function handle(Request $request): string
            {
                $family_code = $request->string('family_code')->toString() ?: null;

                if ($denied = $this->outer->denyImportExecution($this->context)) {
                    return $denied;
                }

                if (! $this->context->hasFiles()) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-no-file')]);
                }

                $filePath = $this->context->uploadedFilePaths[0];

                if (! file_exists($filePath)) {
                    return json_encode(['error' => trans('ai-agent::app.common.invalid-file-path')]);
                }

                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                $rows = match ($ext) {
                    'csv'         => $this->outer->parseCsv($filePath),
                    'xlsx', 'xls' => $this->outer->parseXlsx($filePath),
                    default       => null,
                };

                if ($rows === null) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-unsupported-format')]);
                }

                if ($rows === []) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-empty-file')]);
                }

                if (count($rows) > ImportProducts::MAX_IMPORT_ROWS) {
                    return json_encode([
                        'error' => trans('ai-agent::app.common.import-too-large', ['max' => ImportProducts::MAX_IMPORT_ROWS]),
                    ]);
                }

                $headers = array_map(fn (string $h): string => strtolower(trim($h)), array_keys($rows[0]));

                if (! \in_array('sku', $headers, true)) {
                    return json_encode([
                        'error'   => trans('ai-agent::app.common.import-missing-sku-column', ['columns' => implode(', ', $headers)]),
                        'columns' => $headers,
                    ]);
                }

                $familyId = $this->outer->resolveFamilyId($family_code);

                if (! $familyId) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-family-not-found', ['family' => (string) $family_code])]);
                }

                $familyCode = $this->outer->resolveFamilyCode($familyId);

                $normalizedRows = [];
                $skippedInvalidSku = [];

                foreach ($rows as $i => $row) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        $normalizedRow[strtolower(trim((string) $key))] = $value;
                    }

                    $sku = trim((string) ($normalizedRow['sku'] ?? ''));

                    if ($sku === '' || $sku === '0' || ! $this->outer->validateSku($sku)) {
                        $skippedInvalidSku[] = trans('ai-agent::app.common.import-invalid-sku-row', ['row' => $i + 2]);

                        continue;
                    }

                    $normalizedRows[] = $normalizedRow;
                }

                $canCreate = $this->context->hasPermission('catalog.products.create');
                $canEdit = $this->context->hasPermission('catalog.products.edit');
                $aclSkipped = 0;
                $aclErrors = [];
                $filteredRows = [];

                $existingSkus = $this->outer->existingSkuSet(array_merge(
                    array_map(fn (array $row): string => trim((string) ($row['sku'] ?? '')), $normalizedRows),
                    array_map(fn (array $row): string => trim((string) ($row['parent'] ?? '')), $normalizedRows),
                ));

                foreach ($normalizedRows as $row) {
                    $sku = trim((string) ($row['sku'] ?? ''));
                    $parent = trim((string) ($row['parent'] ?? ''));
                    $productExists = isset($existingSkus[$sku]);

                    if ($productExists && ! $canEdit) {
                        $aclSkipped++;
                        $aclErrors[] = trans('ai-agent::app.common.import-acl-skip-update', ['sku' => $sku]);

                        continue;
                    }

                    if (! $productExists && ! $canCreate) {
                        $aclSkipped++;
                        $aclErrors[] = trans('ai-agent::app.common.import-acl-skip-create', ['sku' => $sku]);

                        continue;
                    }

                    // Attaching a variant to an already-existing parent mutates
                    // that parent, so it requires edit rights even when the
                    // variant row itself is a create.
                    if ($parent !== '' && isset($existingSkus[$parent]) && ! $canEdit) {
                        $aclSkipped++;
                        $aclErrors[] = trans('ai-agent::app.common.import-acl-skip-parent', ['sku' => $sku, 'parent' => $parent]);

                        continue;
                    }

                    $filteredRows[] = $row;
                }

                if ($filteredRows === []) {
                    return json_encode([
                        'error'   => trans('ai-agent::app.common.import-no-eligible-rows'),
                        'skipped' => count($skippedInvalidSku) + $aclSkipped,
                    ]);
                }

                $familyAttrs = $this->outer->writerService()->getFamilyAttributesPublic($familyId);
                $currencies = DB::table('currencies')->where('status', 1)->pluck('code')->toArray() ?: ['USD'];

                $csv = $this->outer->normalizer()->toCsv(
                    $filteredRows,
                    $familyAttrs,
                    $currencies,
                    $familyCode,
                    $this->context->channel,
                    $this->context->locale,
                );

                $storedFilePath = $this->outer->storeNormalizedCsv($csv);
                $jobInstance = $this->outer->createJobInstance($storedFilePath, $familyCode, $this->context);
                $jobTrack = $this->outer->createJobTrack($jobInstance);

                dispatch(new ImportTrackBatch($jobTrack));

                return json_encode([
                    'result' => [
                        'total_rows'  => count($rows),
                        'queued_rows' => count($filteredRows),
                        'skipped'     => count($skippedInvalidSku) + $aclSkipped,
                        'errors'      => $aclErrors === [] ? null : array_slice($aclErrors, 0, 5),
                        'tracker_id'  => $jobTrack->id,
                        'tracker_url' => route('admin.settings.data_transfer.imports.import-view', $jobInstance->id),
                        'message'     => trans('ai-agent::app.common.import-queued', ['count' => count($filteredRows)]),
                    ],
                ]);
            }
        };
    }

    /**
     * Public accessor for the product writer service.
     */
    public function writerService(): ProductWriterService
    {
        return $this->writerService;
    }

    /**
     * Public accessor for the CSV normalizer.
     */
    public function normalizer(): ProductImportCsvNormalizer
    {
        return $this->normalizer;
    }

    /**
     * Store the normalized core-compatible CSV on the private disk (where the
     * core CSV source reads from) with a `.csv` name so the importer selects
     * the CSV source.
     */
    public function storeNormalizedCsv(string $csv): string
    {
        $path = 'imports/ai-import-'.time().'-'.Str::random(12).'.csv';

        Storage::disk('private')->put($path, $csv);

        return $path;
    }

    /**
     * Create a job instance so the AI import appears in import history and can
     * be paused/resumed/cancelled from the standard import tracker.
     */
    public function createJobInstance(
        string $storedFilePath,
        string $familyCode,
        ChatContext $context,
    ): mixed {
        return $this->jobInstancesRepository->create([
            'code'                => 'ai-agent-import-'.Str::lower(Str::random(10)),
            'entity_type'         => 'products',
            'type'                => 'import',
            'action'              => ImportHelper::ACTION_APPEND,
            'validation_strategy' => ImportHelper::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ProductImportCsvNormalizer::DELIMITER,
            'file_path'           => $storedFilePath,
            'filters'             => [
                'family_code' => $familyCode,
                'channel'     => $context->channel,
                'locale'      => $context->locale,
            ],
        ]);
    }

    /**
     * Create the tracker row for the AI-driven import.
     */
    public function createJobTrack(mixed $jobInstance): mixed
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
     * Parse a CSV file into an array of associative rows.
     *
     * @return array<int, array<string, string>>|null
     */
    public function parseCsv(string $filePath): ?array
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

        $headers = fgetcsv($handle, 0, $delimiter, escape: '\\');

        if (! $headers) {
            fclose($handle);

            return null;
        }

        // Strip BOM from first header
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter, escape: '\\')) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);

            $rows[] = $row;

            // Stop past the cap so a hostile file cannot exhaust memory; the
            // caller rejects the oversized upload.
            if (count($rows) > self::MAX_IMPORT_ROWS) {
                break;
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
    public function parseXlsx(string $filePath): ?array
    {
        if (! class_exists(IOFactory::class)) {
            return null;
        }

        try {
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
                    $rows[] = $row;
                }

                if (count($rows) > self::MAX_IMPORT_ROWS) {
                    break;
                }
            }

            return $rows;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Validate that a SKU matches the accepted format.
     * Uses the same pattern as Webkul\Core\Rules\Sku.
     */
    public function validateSku(string $sku): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9]+(?:[-_][a-zA-Z0-9]+)*$/', $sku);
    }

    /**
     * Resolve attribute family ID from code or return the default.
     */
    public function resolveFamilyId(?string $familyCode): ?int
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
     * Resolve the attribute family CODE from its id (the importer expects the
     * code, not the id, in the CSV `attribute_family` column).
     */
    public function resolveFamilyCode(int $familyId): string
    {
        return (string) DB::table('attribute_families')->where('id', $familyId)->value('code');
    }

    /**
     * Build a lookup set of the SKUs that already exist, resolved in chunked
     * `whereIn` queries so a large import stays a handful of queries rather
     * than one existence query per row.
     *
     * @param  array<int, string>  $skus
     * @return array<string, true>
     */
    public function existingSkuSet(array $skus): array
    {
        $unique = array_values(array_unique(array_filter($skus, fn (string $sku): bool => $sku !== '')));

        $existing = [];

        foreach (array_chunk($unique, 5000) as $chunk) {
            foreach (DB::table('products')->whereIn('sku', $chunk)->pluck('sku') as $sku) {
                $existing[$sku] = true;
            }
        }

        return $existing;
    }

    /**
     * Ensure the current user can execute AI imports.
     */
    public function denyImportExecution(ChatContext $context): ?string
    {
        if ($denied = $this->denyUnlessAllowed($context, 'data_transfer.imports.execute')) {
            return $denied;
        }

        $canCreate = $context->hasPermission('catalog.products.create');
        $canEdit = $context->hasPermission('catalog.products.edit');

        return ($canCreate || $canEdit) ? null : $this->formatPermissionDenied('catalog.products.create or catalog.products.edit');
    }

    /**
     * Build a consistent permission denied response for tool execution.
     */
    public function formatPermissionDenied(string $permission): string
    {
        return json_encode([
            'error' => trans('ai-agent::app.common.permission-denied', ['permission' => $permission]),
        ]);
    }
}
