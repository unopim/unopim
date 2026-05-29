<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Jobs\ImportProductsJob;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;

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
     * Detected CSV delimiter for the current import.
     */
    public string $detectedDelimiter = ',';

    public function __construct(
        protected ProductWriterService $writerService,
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
                return 'Import or update products from an uploaded CSV/XLSX file. The file must have a "sku" column. Existing SKUs are updated, new SKUs are created. Call this when the user uploads a spreadsheet file.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'mode'        => $schema->string()->enum(['create_or_update', 'update_only', 'create_only'])->description('Import mode'),
                    'family_code' => $schema->string()->description('Attribute family code to use for new products (default: first available)'),
                ];
            }

            public function handle(Request $request): string
            {
                $mode = $request->string('mode')->toString() ?: 'create_or_update';
                $family_code = $request->string('family_code')->toString() ?: null;

                if ($denied = $this->outer->denyImportExecution($this->context, $mode)) {
                    return $denied;
                }

                if (! $this->context->hasFiles()) {
                    return json_encode(['error' => trans('ai-agent::app.common.import-no-file')]);
                }

                $filePath = $this->context->uploadedFilePaths[0];
                $originalFileName = basename($filePath);

                if (! file_exists($filePath)) {
                    return json_encode(['error' => trans('ai-agent::app.common.invalid-file-path')]);
                }

                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $this->outer->detectedDelimiter = ',';

                $rows = match ($ext) {
                    'csv'         => $this->outer->parseCsv($filePath),
                    'xlsx', 'xls' => $this->outer->parseXlsx($filePath),
                    default       => null,
                };

                if ($rows === null) {
                    return json_encode(['error' => 'Unsupported file format. Please upload a CSV or XLSX file.']);
                }

                if ($rows === []) {
                    return json_encode(['error' => 'The file is empty or could not be parsed.']);
                }

                // Normalize headers to lowercase
                $headers = array_map(fn (mixed $h) => strtolower(trim((string) $h)), array_keys($rows[0]));
                $skuIndex = array_search('sku', $headers, true);

                if ($skuIndex === false) {
                    return json_encode([
                        'error'   => 'CSV must have a "sku" column. Found columns: '.implode(', ', $headers),
                        'columns' => $headers,
                    ]);
                }

                // Resolve attribute family
                $familyId = $this->outer->resolveFamilyId($family_code);

                if (! $familyId) {
                    return json_encode(['error' => "Attribute family '{$family_code}' not found."]);
                }

                // Normalize all rows to lowercase keys before dispatching
                $normalizedRows = [];
                $skippedInvalidSku = [];

                foreach ($rows as $i => $row) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        $normalizedRow[strtolower(trim((string) $key))] = $value;
                    }

                    $sku = trim((string) ($normalizedRow['sku'] ?? ''));

                    if ($sku === '' || $sku === '0' || ! $this->outer->validateSku($sku)) {
                        $skippedInvalidSku[] = 'Row '.($i + 2).': Invalid or empty SKU.';

                        continue;
                    }

                    $normalizedRows[] = $normalizedRow;
                }

                // Per-row ACL filtering: skip rows the user is not permitted to touch
                $canCreate = $this->context->hasPermission('catalog.products.create');
                $canEdit = $this->context->hasPermission('catalog.products.edit');
                $aclSkipped = 0;
                $aclErrors = [];
                $filteredRows = [];

                foreach ($normalizedRows as $row) {
                    $sku = trim((string) ($row['sku'] ?? ''));
                    $productExists = DB::table('products')->where('sku', $sku)->exists();

                    if ($productExists && ! $canEdit) {
                        $aclSkipped++;
                        $aclErrors[] = "SKU '{$sku}' skipped: updating existing products requires 'catalog.products.edit' permission.";

                        continue;
                    }

                    if (! $productExists && ! $canCreate) {
                        $aclSkipped++;
                        $aclErrors[] = "SKU '{$sku}' skipped: creating new products requires 'catalog.products.create' permission.";

                        continue;
                    }

                    $filteredRows[] = $row;
                }

                $storedFilePath = $this->outer->storeImportFile($filePath, $originalFileName);
                $jobInstance = $this->outer->createJobInstance(
                    $storedFilePath,
                    $mode,
                    $family_code,
                    $this->context,
                );
                $jobTrack = $this->outer->createJobTrack($jobInstance);

                $familyAttrs = $this->outer->writerService()->getFamilyAttributesPublic($familyId);
                $currencies = DB::table('currencies')->where('status', 1)->pluck('code')->toArray() ?: ['USD'];

                ImportProductsJob::dispatch(
                    $jobTrack->id,
                    $filteredRows,
                    $mode,
                    $familyId,
                    $familyAttrs,
                    $currencies,
                    $this->context->channel,
                    $this->context->locale,
                );

                // When QUEUE_CONNECTION=sync the job runs inline; read back the actual counts.
                $jobTrack->refresh();
                $summary = $jobTrack->summary;

                if ($summary && isset($summary['created'])) {
                    return json_encode([
                        'result' => [
                            'created' => $summary['created'],
                            'updated' => $summary['updated'],
                            'skipped' => $aclSkipped,
                            'errors'  => $aclErrors,
                        ],
                    ]);
                }

                return json_encode([
                    'result' => [
                        'total_rows'  => count($rows),
                        'queued_rows' => count($filteredRows),
                        'skipped'     => count($skippedInvalidSku) + $aclSkipped,
                        'tracker_id'  => $jobTrack->id,
                        'message'     => 'Import job has been queued. All '.count($filteredRows).' rows will be processed in the background. Check the tracker for progress.',
                    ],
                ]);
            }
        };
    }

    /**
     * Public accessor for protected writerService dependency.
     */
    public function writerService(): ProductWriterService
    {
        return $this->writerService;
    }

    /**
     * Store the uploaded import file on the private disk for history/tracker access.
     */
    public function storeImportFile(string $filePath, string $originalFileName): string
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
    public function createJobInstance(
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

        $this->detectedDelimiter = $delimiter;

        $headers = fgetcsv($handle, 0, $delimiter);

        if (! $headers) {
            fclose($handle);

            return null;
        }

        // Strip BOM from first header
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);

            $rows[] = $row;
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
                    $rows[] = $row;
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
     * Ensure the current user can execute AI imports for the requested mode.
     */
    public function denyImportExecution(ChatContext $context, string $mode): ?string
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
    public function formatPermissionDenied(string $permission): string
    {
        return json_encode([
            'error' => "Permission denied: you do not have '{$permission}' access. Contact your administrator.",
        ]);
    }
}
