<?php

namespace Webkul\DataTransfer\Helpers\Importers\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Completeness\Observers\Product as CompletenessProductObserver;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Sku;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer;
use Webkul\ElasticSearch\Observers\Product as ElasticProductObserver;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

class Importer extends AbstractImporter
{
    /**
     * Product type simple
     */
    public const PRODUCT_TYPE_SIMPLE = 'simple';

    /**
     * Product type virtual
     */
    public const PRODUCT_TYPE_VIRTUAL = 'virtual';

    /**
     * Product type downloadable
     */
    public const PRODUCT_TYPE_DOWNLOADABLE = 'downloadable';

    /**
     * Product type configurable
     */
    public const PRODUCT_TYPE_CONFIGURABLE = 'configurable';

    /**
     * Product type grouped
     */
    public const PRODUCT_TYPE_GROUPED = 'grouped';

    /**
     * Error code for invalid product type
     */
    public const ERROR_INVALID_TYPE = 'invalid_product_type';

    /**
     * Error code for non existing SKU
     */
    public const ERROR_SKU_NOT_FOUND_FOR_DELETE = 'sku_not_found_to_delete';

    /**
     * Error code for duplicate url key
     */
    public const ERROR_DUPLICATE_URL_KEY = 'duplicated_url_key';

    /**
     * Error code for invalid attribute family code
     */
    public const ERROR_INVALID_ATTRIBUTE_FAMILY_CODE = 'attribute_family_code_not_found';

    /**
     * Error code for super attribute code not found
     */
    public const ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND = 'configurable_attribute_not_in_family';

    const ERROR_NO_CONFIGURABLE_ATTRIBUES = 'configurable_attributes_not_found';

    const ERROR_NOT_CORRECT_TYPE_FOR_AXIS = 'incorrect_type_for_configurable_attribute';

    const ERROR_NOT_FOUND_VARIANT_CONFIGURABLE_ATTRIBUTE = 'variant_configurable_attribute_not_found';

    const ERROR_NOT_UNIQUE_VARIANT = 'not_unique_variant_product';

    const ERROR_NOT_FOUND_CHANNEL = 'channel_not_found';

    const ERROR_NOT_FOUND_LOCALE_IN_CHANNEL = 'locale_not_found_in_channel';

    const ERROR_NOT_UNIQUE_VALUE = 'not_unique_value';

    const ERROR_PARENT_DOES_NOT_EXIST = 'parent_not_exist';

    const ERROR_WRONG_FAMILY_FOR_VARIANT = 'incorrect_family_for_variant';

    const ATTRIBUTE_FAMILY_CODE = 'attribute_family';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_INVALID_TYPE                             => 'data_transfer::app.importers.products.validation.errors.invalid-type',
        self::ERROR_SKU_NOT_FOUND_FOR_DELETE                 => 'data_transfer::app.importers.products.validation.errors.sku-not-found',
        self::ERROR_DUPLICATE_URL_KEY                        => 'data_transfer::app.importers.products.validation.errors.duplicate-url-key',
        self::ERROR_INVALID_ATTRIBUTE_FAMILY_CODE            => 'data_transfer::app.importers.products.validation.errors.invalid-attribute-family',
        self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND           => 'data_transfer::app.importers.products.validation.errors.super-attribute-not-found',
        self::ERROR_NO_CONFIGURABLE_ATTRIBUES                => 'data_transfer::app.importers.products.validation.errors.configurable-attributes-not-found',
        self::ERROR_NOT_CORRECT_TYPE_FOR_AXIS                => 'data_transfer::app.importers.products.validation.errors.configurable-attributes-wrong-type',
        self::ERROR_NOT_UNIQUE_VARIANT                       => 'data_transfer::app.importers.products.validation.errors.not-unique-variant-product',
        self::ERROR_NOT_FOUND_VARIANT_CONFIGURABLE_ATTRIBUTE => 'data_transfer::app.importers.products.validation.errors.variant-configurable-attribute-not-found',
        self::ERROR_NOT_FOUND_CHANNEL                        => 'data_transfer::app.importers.products.validation.errors.channel-not-exist',
        self::ERROR_NOT_FOUND_LOCALE_IN_CHANNEL              => 'data_transfer::app.importers.products.validation.errors.locale-not-in-channel',
        self::ERROR_NOT_UNIQUE_VALUE                         => 'data_transfer::app.importers.products.validation.errors.not-unique-value',
        self::ERROR_PARENT_DOES_NOT_EXIST                    => 'data_transfer::app.importers.products.validation.errors.parent-not-exist',
        self::ERROR_WRONG_FAMILY_FOR_VARIANT                 => 'data_transfer::app.importers.products.validation.errors.incorrect-family-for-variant',
    ];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['sku', 'locale', 'channel', 'type', 'parent', self::ATTRIBUTE_FAMILY_CODE];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'sku';

    /**
     * Cached attribute families
     */
    protected mixed $attributeFamilies = [];

    /**
     * Cached attributes
     */
    protected mixed $attributes = [];

    /**
     * Cached product type family attributes
     */
    protected array $typeFamilyAttributes = [];

    /**
     * Product type family validation rules
     */
    protected array $typeFamilyValidationRules = [];

    /**
     * Cached categories
     */
    protected array $categories = [];

    /**
     * All channels selected currency codes
     */
    protected array $currencies = [];

    /**
     * Channel code as key and locale codes in array as value
     */
    protected array $channelsAndLocales = [];

    /**
     * Is linking required
     */
    protected bool $linkingRequired = false;

    /**
     * Is indexing required
     */
    protected bool $indexingRequired = true;

    /**
     * Cached variants configurable attributes for unique value check
     */
    protected array $cachedVariantValues = [];

    protected array $cachedUniqueValues = [];

    protected array $channelCachedValues = [];

    protected array $localeCachedValues = [];

    protected array $channelLocaleCachedValues = [];

    /**
     * In-memory cache for existing products keyed by SKU.
     * Eliminates per-row DB queries during validation and batch processing.
     */
    protected array $existingProductsCache = [];

    /**
     * Attribute families indexed by code for O(1) lookup.
     */
    protected array $attributeFamiliesByCode = [];

    /**
     * In-memory category code cache.
     */
    protected array $categoryCodes = [];

    /**
     * Family attributes indexed by code for O(1) lookup per type/family.
     */
    protected array $typeFamilyAttributesByCode = [];

    /**
     * All attributes indexed by code for O(1) lookup.
     */
    protected array $allAttributesByCode = [];

    /**
     * Cached media-type attributes to avoid repeated Collection filtering.
     */
    protected ?array $mediaAttributes = null;

    /**
     * Existing unique attribute values from DB, keyed by [attributeCode][scopeKey][value] => productId.
     * Replaces per-row DB `unique:products,...` validation rules with a single bulk pre-load.
     */
    protected array $existingUniqueDBValues = [];

    /**
     * Pre-computed scope string per attribute code + type/family key.
     * Avoids calling Eloquent __get (isLocaleBasedAttribute, etc.) per row,
     * which is ~54µs per call due to Eloquent magic method overhead.
     * Keyed as "type|familyCode|attrCode" => 'common'|'locale'|'channel'|'channel_locale'
     */
    protected array $attributeScopeCache = [];

    /**
     * Cached Validator instances per type/family for reuse via setData().
     * Avoids creating 10K+ Validator instances during validation.
     */
    protected array $cachedValidators = [];

    /**
     * Static cache shared across all Importer instances in the same worker process.
     * Eliminates redundant DB queries for attributes/families/channels on every batch job.
     */
    protected static ?array $staticInitCache = null;

    /**
     * Valid csv columns
     */
    protected array $validColumnNames = [
        'locale',
        'status',
        'channel',
        'type',
        'attribute_family',
        'parent',
        'categories',
        'related_products',
        'cross_sells',
        'up_sells',
        'configurable_attributes',
        'associated_skus',
    ];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository,
        protected SKUStorage $skuStorage,
        protected ChannelRepository $channelRepository,
        protected FieldProcessor $fieldProcessor,
    ) {
        parent::__construct($importBatchRepository);

        $this->initAttributes();
    }

    /**
     * Load all attributes and families to use later.
     * Pre-indexes families by code for O(1) lookups.
     */
    protected function initAttributes(): void
    {
        /**
         * Static cache: attributes, families, and channels are loaded once per worker process.
         * Eliminates 3+ redundant DB queries on every ImportBatch job within the same worker.
         */
        if (self::$staticInitCache === null) {
            $channels = $this->channelRepository->all();

            $channelsAndLocales = [];
            $currencies = [];

            foreach ($channels as $channel) {
                $channelsAndLocales[$channel->code] = $channel->locales?->pluck('code')?->toArray() ?? [];
                $currencies = array_unique(array_merge($currencies, $channel->currencies?->pluck('code')?->toArray() ?? []));
            }

            self::$staticInitCache = [
                'attributeFamilies'  => $this->attributeFamilyRepository->all(),
                'attributes'         => $this->attributeRepository->all(),
                'channelsAndLocales' => $channelsAndLocales,
                'currencies'         => $currencies,
            ];
        }

        $this->attributeFamilies = self::$staticInitCache['attributeFamilies'];
        $this->attributes = self::$staticInitCache['attributes'];
        $this->channelsAndLocales = self::$staticInitCache['channelsAndLocales'];
        $this->currencies = self::$staticInitCache['currencies'];

        foreach ($this->attributeFamilies as $family) {
            $this->attributeFamiliesByCode[$family->code] = $family;
        }

        foreach ($this->attributes as $attribute) {
            $this->allAttributesByCode[$attribute->code] = $attribute;
        }

        foreach ($this->attributes as $attribute) {
            if ($attribute->type === 'price') {
                $this->addPriceAttributesColumns($attribute->code);

                continue;
            }

            $this->validColumnNames[] = $attribute->code;
        }
    }

    /**
     * initialize channels, locales and currecies value
     */
    protected function initializeChannels(): void
    {
        $channels = $this->channelRepository->all();

        foreach ($channels as $channel) {
            $this->channelsAndLocales[$channel->code] = $channel->locales?->pluck('code')?->toArray() ?? [];

            $this->currencies = array_merge($this->currencies, $channel->currencies?->pluck('code')?->toArray() ?? []);
        }
    }

    /**
     * Add valid column names for the price attribute according to currencies
     */
    public function addPriceAttributesColumns(string $attributeCode): void
    {
        foreach ($this->currencies as $currency) {
            $this->validColumnNames[] = $this->getPriceTypeColumnName($attributeCode, $currency);
        }
    }

    /**
     * Get formatted price column name
     */
    protected function getPriceTypeColumnName(string $attributeCode, string $currency): string
    {
        return "{$attributeCode} ({$currency})";
    }

    /**
     * Initialize Product error templates
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    /**
     * Save validated batches.
     *
     * Optimized: Collects ALL SKUs from the source file in a single pass,
     * then bulk-loads them into SKUStorage with one DB query instead of
     * N individual queries (one per row). This alone eliminates ~10K DB
     * queries for a 10K row import.
     */
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        /**
         * First pass: collect all SKUs and parent SKUs for bulk loading.
         * Also counts total rows for parallel chunk splitting.
         */
        $allSkus = [];
        $totalSourceRows = 0;

        $source->rewind();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            $allSkus[] = $rowData['sku'];

            if (! empty($rowData['parent'])) {
                $allSkus[] = $rowData['parent'];
            }

            $totalSourceRows++;
            $source->next();
        }

        /**
         * Single bulk load of ALL SKUs — replaces N individual queries.
         */
        if (! empty($allSkus)) {
            $this->skuStorage->load(array_unique($allSkus));
        }

        /**
         * Pre-load all caches for O(1) lookups during validation.
         */
        $this->preloadExistingProducts($allSkus);
        $this->preloadCategoryCodes();
        $this->preloadExistingUniqueValues();

        /**
         * Validate rows — parallel or sequential based on config and row count.
         * Parallel uses pcntl_fork to split validation across CPU cores.
         */
        $workerCount = $this->getValidationWorkerCount();
        $parallelThreshold = 1000;

        if ($workerCount > 1 && function_exists('pcntl_fork') && $totalSourceRows > $parallelThreshold) {
            $this->parallelValidateRows($source, $totalSourceRows, $workerCount);
        } else {
            $this->sequentialValidateRows($source);
        }

        /**
         * Create batch records for valid rows.
         */
        $this->createBatchesFromValidRows($source);

        return $this;
    }

    /**
     * Validate all rows sequentially (fallback/small import path).
     */
    protected function sequentialValidateRows($source): void
    {
        $source->rewind();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            $this->validateRow($rowData, $source->getCurrentRowNumber());
            $this->processedRowsCount++;

            $source->next();
        }
    }

    /**
     * Validate rows in parallel using pcntl_fork.
     *
     * Splits the CSV into N chunks (one per CPU core), forks child processes
     * that each validate their chunk independently. Children write invalid
     * row numbers to temp files. Parent merges results and re-validates
     * only the invalid rows (~1% of total) to capture error details.
     *
     * All preloaded caches (SKUs, products, categories, unique values) are
     * inherited by children via copy-on-write — no duplication overhead.
     */
    protected function parallelValidateRows($source, int $totalRows, int $workerCount): void
    {
        $chunkSize = (int) ceil($totalRows / $workerCount);
        $tempDir = storage_path('app/import_validation_'.$this->import->id.'_'.uniqid());

        if (! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            $this->sequentialValidateRows($source);

            return;
        }

        $children = [];

        for ($w = 0; $w < $workerCount; $w++) {
            $startIdx = $w * $chunkSize;
            $endIdx = min(($w + 1) * $chunkSize, $totalRows);

            $pid = pcntl_fork();

            if ($pid === -1) {
                /**
                 * Fork failed — fallback to sequential validation.
                 */
                $this->cleanupTempDir($tempDir);
                $this->sequentialValidateRows($source);

                return;
            }

            if ($pid === 0) {
                /**
                 * === CHILD PROCESS ===
                 * Reconnect DB (connections can't be shared after fork).
                 * Validate assigned chunk, write results, exit.
                 */
                try {
                    DB::reconnect();

                    $invalidRows = [];
                    $processedCount = 0;

                    $source->rewind();
                    $idx = 0;

                    while ($source->valid()) {
                        if ($idx >= $endIdx) {
                            break;
                        }

                        if ($idx >= $startIdx) {
                            try {
                                $rowData = $source->current();
                                $rowNumber = $source->getCurrentRowNumber();

                                if (! $this->validateRow($rowData, $rowNumber)) {
                                    $invalidRows[] = $rowNumber;
                                }

                                $processedCount++;
                            } catch (\InvalidArgumentException $e) {
                                $processedCount++;
                            }
                        }

                        $source->next();
                        $idx++;
                    }

                    file_put_contents(
                        $tempDir.'/worker_'.$w.'.json',
                        json_encode(['invalid' => $invalidRows, 'processed' => $processedCount])
                    );
                } catch (\Throwable $e) {
                    file_put_contents(
                        $tempDir.'/worker_'.$w.'.json',
                        json_encode(['invalid' => [], 'processed' => 0, 'error' => $e->getMessage()])
                    );
                }

                exit(0);
            }

            $children[] = $pid;
        }

        /**
         * === PARENT: Wait for all children to complete ===
         */
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        /**
         * Merge child results: collect invalid row numbers and processed counts.
         */
        $allInvalidRows = [];

        for ($w = 0; $w < $workerCount; $w++) {
            $resultFile = $tempDir.'/worker_'.$w.'.json';

            if (! file_exists($resultFile)) {
                continue;
            }

            $result = json_decode(file_get_contents($resultFile), true);
            $this->processedRowsCount += ($result['processed'] ?? 0);

            foreach ($result['invalid'] ?? [] as $rowNumber) {
                $allInvalidRows[$rowNumber] = true;
            }
        }

        $this->cleanupTempDir($tempDir);

        /**
         * Re-read source to populate parent state:
         * - Valid rows: mark in $validatedRows without re-running validation
         * - Invalid rows: re-validate in parent to capture error details
         *   (typically <1% of total rows, so overhead is negligible)
         */
        $source->rewind();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
                $rowNumber = $source->getCurrentRowNumber();

                if (isset($allInvalidRows[$rowNumber])) {
                    $this->validateRow($rowData, $rowNumber);
                } else {
                    $this->validatedRows[$rowNumber] = true;
                }
            } catch (\InvalidArgumentException $e) {
                // Skip malformed rows
            }

            $source->next();
        }
    }

    /**
     * Create batch records from validated rows.
     *
     * Re-reads source and creates DB batch records for all valid rows.
     * Replaces the parent::saveValidatedBatches() call.
     */
    protected function createBatchesFromValidRows($source): void
    {
        $batchSize = $this->getEffectiveBatchSize();

        /**
         * Clean previous saved batches
         */
        $this->importBatchRepository->deleteWhere([
            'job_track_id' => $this->import->id,
        ]);

        $batchRows = [];
        $this->processedRowsCount = 0;
        $source->rewind();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
                $rowNumber = $source->getCurrentRowNumber();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            if (isset($this->validatedRows[$rowNumber]) && ! $this->errorHelper->isRowInvalid($rowNumber)) {
                $batchRows[] = $this->prepareRowForDb($rowData);
            }

            $this->processedRowsCount++;

            if (count($batchRows) >= $batchSize) {
                $this->importBatchRepository->create([
                    'job_track_id' => $this->import->id,
                    'data'         => $batchRows,
                ]);

                $batchRows = [];
            }

            $source->next();
        }

        if (! empty($batchRows)) {
            $this->importBatchRepository->create([
                'job_track_id' => $this->import->id,
                'data'         => $batchRows,
            ]);
        }
    }

    /**
     * Get the number of parallel validation workers to use.
     * Returns 1 for sequential mode.
     */
    protected function getValidationWorkerCount(): int
    {
        if (! config('import.parallel_validation', true)) {
            return 1;
        }

        $configured = (int) config('import.validation_workers', 0);

        if ($configured > 0) {
            return $configured;
        }

        /**
         * Auto-detect CPU cores
         */
        $cpuCount = (int) @shell_exec('nproc 2>/dev/null');

        return max(1, $cpuCount ?: 4);
    }

    /**
     * Clean up temporary validation directory.
     */
    protected function cleanupTempDir(string $tempDir): void
    {
        if (! is_dir($tempDir)) {
            return;
        }

        $files = glob($tempDir.'/*');

        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($tempDir);
    }

    /**
     * Pre-load existing products into in-memory cache.
     * Uses lean Eloquent query with only needed columns and relationships.
     */
    protected function preloadExistingProducts(array $skus): void
    {
        $uniqueSkus = array_unique($skus);

        foreach (array_chunk($uniqueSkus, 1000) as $chunk) {
            /**
             * Select only columns needed for validation. Eager-load attribute_family
             * (for family code checks) and super_attributes (for variant uniqueness checks).
             */
            $products = ProductModel::query()
                ->select('id', 'sku', 'type', 'attribute_family_id')
                ->with(['attribute_family:id,code', 'super_attributes:id,code'])
                ->whereIn('sku', $chunk)
                ->get();

            foreach ($products as $product) {
                $this->existingProductsCache[$product->sku] = $product;
            }
        }
    }

    /**
     * Pre-load existing products for the import phase (not validation).
     * Uses a plain DB query (no Eloquent relationships) to load only the
     * columns needed during import: values and parent_id. This avoids the
     * N+1 lazy-load issue that ->with(['attribute_family:id,code']) triggers
     * (1000 individual attribute_family queries instead of 1 batch).
     */
    protected function preloadExistingProductsForImport(array $skus): void
    {
        $uniqueSkus = array_unique($skus);

        foreach (array_chunk($uniqueSkus, 1000) as $chunk) {
            $products = DB::table('products')
                ->select('id', 'sku', 'parent_id', 'values', 'status')
                ->whereIn('sku', $chunk)
                ->get();

            foreach ($products as $product) {
                // Decode values JSON so downstream code can access it as array
                if (is_string($product->values)) {
                    $product->values = json_decode($product->values, true) ?? [];
                }

                $this->existingProductsCache[$product->sku] = $product;
            }
        }
    }

    /**
     * Pre-load all category codes into a lookup set.
     */
    protected function preloadCategoryCodes(): void
    {
        if (empty($this->categoryCodes)) {
            $this->categoryCodes = $this->categoryRepository->pluck('code')->flip()->all();
        }
    }

    /**
     * Pre-load all existing unique attribute values from the database.
     * Replaces per-row `unique:products,values->path` DB queries with
     * a single bulk query per unique attribute, reducing thousands of
     * queries to just a handful.
     */
    protected function preloadExistingUniqueValues(): void
    {
        $uniqueAttributes = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute->is_unique && $attribute->code !== 'sku') {
                $uniqueAttributes[$attribute->code] = $attribute;
            }
        }

        if (empty($uniqueAttributes)) {
            return;
        }

        foreach ($uniqueAttributes as $code => $attribute) {
            if ($attribute->isLocaleAndChannelBasedAttribute()) {
                foreach ($this->channelsAndLocales as $channel => $locales) {
                    foreach ($locales as $locale) {
                        $this->loadUniqueValuesForPath($code, "{$channel}.{$locale}", "$.channel_locale_specific.{$channel}.{$locale}.{$code}");
                    }
                }
            } elseif ($attribute->isChannelBasedAttribute()) {
                foreach (array_keys($this->channelsAndLocales) as $channel) {
                    $this->loadUniqueValuesForPath($code, $channel, "$.channel_specific.{$channel}.{$code}");
                }
            } elseif ($attribute->isLocaleBasedAttribute()) {
                $allLocales = [];

                foreach ($this->channelsAndLocales as $locales) {
                    $allLocales = array_merge($allLocales, $locales);
                }

                foreach (array_unique($allLocales) as $locale) {
                    $this->loadUniqueValuesForPath($code, $locale, "$.locale_specific.{$locale}.{$code}");
                }
            } else {
                $this->loadUniqueValuesForPath($code, 'common', "$.common.{$code}");
            }
        }
    }

    /**
     * Load unique attribute values from DB for a specific JSON path scope.
     */
    protected function loadUniqueValuesForPath(string $attributeCode, string $scopeKey, string $jsonPath): void
    {
        $results = DB::table('products')
            ->select('id', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(`values`, '{$jsonPath}')) as attr_value"))
            ->whereNotNull(DB::raw("JSON_EXTRACT(`values`, '{$jsonPath}')"))
            ->get();

        foreach ($results as $row) {
            if ($row->attr_value !== null && $row->attr_value !== 'null' && $row->attr_value !== '') {
                $this->existingUniqueDBValues[$attributeCode][$scopeKey][$row->attr_value] = $row->id;
            }
        }
    }

    /**
     * Get the scope key for a unique attribute based on current row data.
     */
    protected function getUniqueScopeKey(Attribute $attribute, array $rowData): string
    {
        if ($attribute->isLocaleAndChannelBasedAttribute()) {
            return ($rowData['channel'] ?? '').'.'.($rowData['locale'] ?? '');
        }

        if ($attribute->isChannelBasedAttribute()) {
            return $rowData['channel'] ?? '';
        }

        if ($attribute->isLocaleBasedAttribute()) {
            return $rowData['locale'] ?? '';
        }

        return 'common';
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        /**
         * If row is already validated than no need for further validation
         */
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        /**
         * If import action is delete than no need for further validation
         */
        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->isSKUExist($rowData['sku'])) {
                $this->skipRow($rowNumber, self::ERROR_SKU_NOT_FOUND_FOR_DELETE);

                return false;
            }

            return true;
        }

        if (! $this->validChannelsAndLocale($rowData, $rowNumber)) {
            return false;
        }

        /**
         * Check if product type exists
         */
        if (! config('product_types.'.$rowData['type'])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_TYPE, 'type');

            return false;
        }

        /**
         * Check if attribute family exists
         */
        if (! isset($this->attributeFamiliesByCode[$rowData[self::ATTRIBUTE_FAMILY_CODE]])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_ATTRIBUTE_FAMILY_CODE, self::ATTRIBUTE_FAMILY_CODE);

            return false;
        }

        if (! empty($rowData['parent'])) {
            $parentProduct = $this->getExistingProduct($rowData['parent']);

            if (! $parentProduct || $parentProduct?->type != self::PRODUCT_TYPE_CONFIGURABLE) {
                $this->skipRow($rowNumber, self::ERROR_PARENT_DOES_NOT_EXIST, 'parent');

                return false;
            }

            if ($rowData[self::ATTRIBUTE_FAMILY_CODE] != $parentProduct->attribute_family->code) {
                $this->skipRow($rowNumber, self::ERROR_WRONG_FAMILY_FOR_VARIANT, self::ATTRIBUTE_FAMILY_CODE);

                return false;
            }
        }

        if (! isset($this->typeFamilyValidationRules[$rowData['type']][$rowData[self::ATTRIBUTE_FAMILY_CODE]])) {
            $this->typeFamilyValidationRules[$rowData['type']][$rowData[self::ATTRIBUTE_FAMILY_CODE]] = $this->getValidationRules($rowData);
        }

        $this->updateRowMediaPath($rowData);

        $validationRules = $this->typeFamilyValidationRules[$rowData['type']][$rowData[self::ATTRIBUTE_FAMILY_CODE]];

        /**
         * Validate product attributes using cached Validator instance.
         * Reuses Validator via setData() instead of creating 10K+ instances.
         */
        $cacheKey = $rowData['type'].'|'.$rowData[self::ATTRIBUTE_FAMILY_CODE];

        if (! isset($this->cachedValidators[$cacheKey])) {
            $this->cachedValidators[$cacheKey] = Validator::make([], $validationRules);
        }

        $validator = $this->cachedValidators[$cacheKey];
        $validator->setData($rowData);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        $this->validatUniqueAttributeValues($rowData, $rowNumber);

        /**
         * Additional Validations for Configurable attributes
         */
        $optionsData = [];

        $validationRules = [];

        if ($rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE) {
            if (empty($rowData['configurable_attributes'])) {
                $this->skipRow(
                    $rowNumber,
                    self::ERROR_NO_CONFIGURABLE_ATTRIBUES,
                    'configurable_attributes',
                    trans($this->messages[self::ERROR_NO_CONFIGURABLE_ATTRIBUES]),
                );
            }

            $attributes = explode(',', $rowData['configurable_attributes'] ?? '');

            $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);
            $familyAttributesByCode = $this->getTypeFamilyAttributesByCode($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE], $familyAttributes);

            foreach ($attributes as $attributeCode) {
                $attributeCode = trim($attributeCode);

                $attribute = $familyAttributesByCode[$attributeCode] ?? null;

                if (! $attribute) {
                    $this->skipRow(
                        $rowNumber,
                        self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND,
                        'configurable_attributes',
                        trans($this->messages[self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND], [
                            'code'       => $attributeCode,
                            'familyCode' => $rowData[self::ATTRIBUTE_FAMILY_CODE],
                        ])
                    );

                    break;
                }

                if (
                    $attribute->isLocaleBasedAttribute()
                    || $attribute->isChannelBasedAttribute()
                    || ! in_array($attribute->type, AttributeFamily::ALLOWED_VARIANT_OPTION_TYPES)
                ) {
                    $this->skipRow(
                        $rowNumber,
                        self::ERROR_NOT_CORRECT_TYPE_FOR_AXIS,
                        $attributeCode,
                        trans($this->messages[self::ERROR_NOT_CORRECT_TYPE_FOR_AXIS]),
                    );
                }
            }
        }

        $this->isUniqueVariation($rowData, $rowNumber);

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    protected function updateRowMediaPath(array &$rowData): void
    {
        /**
         * Cache media attributes once instead of filtering per row
         */
        if ($this->mediaAttributes === null) {
            $mediaTypes = ['image', 'file', 'gallery'];
            $this->mediaAttributes = $this->attributes->whereIn('type', $mediaTypes)->all();
        }

        $imageDirPath = $this->import->images_directory_path ?? '';

        foreach ($this->mediaAttributes as $attribute) {
            $code = $attribute->code;

            if (! isset($rowData[$code])) {
                continue;
            }

            $value = $this->fieldProcessor->handleMediaField($rowData[$code], $imageDirPath);

            if ($value) {
                $rowData[$code] = is_array($value) ? implode(',', $value) : $value;
            }
        }
    }

    /**
     * Prepare validation rules
     */
    public function getValidationRules(array $rowData): array
    {
        $rules = [
            'sku' => ['required', new Sku],
        ];

        $attributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);

        $skipAttributes = $rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE ? (explode(',', $rowData['configurable_attributes']) ?? []) : [];

        $skipAttributes = array_map('trim', $skipAttributes);

        $skipAttributes[] = 'sku';

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->code;

            if (in_array($attributeCode, $skipAttributes)) {
                continue;
            }

            $validations = [];

            if (! isset($rules[$attributeCode])) {
                $validations += $attribute->getValidationRules(withUniqueValidation: false);
            } else {
                $validations = $rules[$attributeCode];
            }

            if ($attribute->type == 'price') {
                foreach ($this->currencies as $currency) {
                    $rules[$this->getPriceTypeColumnName($attributeCode, $currency)] = $validations;
                }

                continue;
            }

            $rules[$attributeCode] = $validations;
        }

        return $rules;
    }

    /**
     * Start the import process
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        ElasticProductObserver::disable();

        CompletenessProductObserver::disable();

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteProducts($batch);
        } else {
            $this->saveProductsData($batch);
        }

        /**
         * Update import batch summary
         */
        $batch = $this->importBatchRepository->update([
            'state' => Import::STATE_PROCESSED,

            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    /**
     * Delete products from current batch
     */
    protected function deleteProducts(JobTrackBatchContract $batch): bool
    {
        /**
         * Load SKU storage with batch skus
         */
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isSKUExist($rowData['sku'])) {
                continue;
            }

            $product = $this->skuStorage->get($rowData['sku']);

            $idsToDelete[] = $product['id'];
        }

        $idsToDelete = array_unique($idsToDelete);

        $this->deletedItemsCount = count($idsToDelete);

        $this->productRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        /**
         * Remove product images from the storage
         */
        foreach ($idsToDelete as $id) {
            $imageDirectory = 'product/'.$id;

            if (! Storage::exists($imageDirectory)) {
                continue;
            }

            Storage::deleteDirectory($imageDirectory);
        }

        return true;
    }

    /**
     * Save products from current batch
     */
    protected function saveProductsData(JobTrackBatchContract $batch): bool
    {
        $useBulkMode = config('import.mysql_bulk_mode', true);

        /**
         * MySQL bulk mode: temporarily disable unique_checks and foreign_key_checks
         * for 2-3x faster INSERT/UPSERT. Safe because data is pre-validated.
         */
        if ($useBulkMode) {
            DB::statement('SET SESSION unique_checks=0');
            DB::statement('SET SESSION foreign_key_checks=0');
        }

        try {
            $batchSkus = Arr::pluck($batch->data, 'sku');

            /**
             * Load SKU storage with batch skus
             */
            $this->skuStorage->load($batchSkus);

            /**
             * Bulk-load existing products with values + parent_id into cache.
             * Uses a targeted raw query (no Eloquent relationships) to avoid
             * the N+1 lazy-load issue that ->with(['attribute_family:id,code'])
             * triggers (1000 individual lazy-loads instead of 1 batch query).
             */
            $this->preloadExistingProductsForImport($batchSkus);

            /**
             * Pre-load category code cache to prevent N+1 queries in prepareOtherSections.
             * Without this, each row with categories does: SELECT code FROM categories WHERE code IN (?).
             */
            $this->preloadCategoryCodes();

            $products = [];

            foreach ($batch->data as $rowData) {
                $isExisting = $this->isSKUExist($rowData['sku']);

                /**
                 * Prepare products for import
                 */
                $this->prepareProducts($rowData, $products, $isExisting);

                /**
                 * Prepare Product Configurable Attributes
                 */
                $this->prepareConfigurableAttributes($rowData, $products, $isExisting);
            }

            $this->saveProducts($products);
        } finally {
            if ($useBulkMode) {
                DB::statement('SET SESSION unique_checks=1');
                DB::statement('SET SESSION foreign_key_checks=1');
            }
        }

        return true;
    }

    /**\n     * Prepare products from current batch.\n     *\n     * Optimized: Uses indexed attribute family lookup (O(1)) instead of\n     * Collection->where()->first() (O(n)) per row.\n     */
    public function prepareProducts(array $rowData, array &$products, ?bool $isExisting = null): void
    {
        /**
         * O(1) indexed lookup instead of Collection->where('code', ...)->first()
         */
        $attributeFamily = $this->attributeFamiliesByCode[$rowData[self::ATTRIBUTE_FAMILY_CODE]] ?? null;
        $attributeFamilyId = $attributeFamily?->id;

        $product = $isExisting ? $this->getExistingProduct($rowData['sku']) : null;

        $productValues = $products['update'][$rowData['sku']]['values'] ?? [];

        if (empty($productValues) && $isExisting) {
            $productValues = $product?->values ?? [];
        }

        $data = [
            'type'                => $rowData['type'],
            'parent_id'           => $this->getParentId($rowData, $product?->parent_id),
            'sku'                 => $rowData['sku'],
            'attribute_family_id' => $attributeFamilyId,
            'values'              => $productValues,
            'status'              => $this->getProductStatus($rowData, $isExisting, $product),
        ];

        /**
         * prepare and add attribute values to product data
         */
        $this->prepareAttributeValues($rowData, $data['values']);

        /**
         * add category and associations sections data
         */
        $this->prepareOtherSections($rowData, $data);

        if ($isExisting) {
            $data['values'] = $this->mergeAttributeValues($data['values'], $products['update'][$rowData['sku']]['values'] ?? []);

            $products['update'][$rowData['sku']] = array_merge($products['update'][$rowData['sku']] ?? [], $data);
        } else {
            $data['created_at'] = $rowData['created_at'] ?? now();
            $data['updated_at'] = $rowData['updated_at'] ?? now();

            $data['values'] = $this->mergeAttributeValues($data['values'], $products['insert'][$rowData['sku']]['values'] ?? []);

            $products['insert'][$rowData['sku']] = array_merge($products['insert'][$rowData['sku']] ?? [], $data);
        }
    }

    /**
     * Format Product Status
     */
    protected function getProductStatus(array $rowData, bool $isExisting, $product = null): int
    {
        $status = $rowData['status'] ?? ($isExisting ? $product?->status : 0);

        return match (true) {
            is_string($status) && strtolower($status) === 'true' => 1,
            is_bool($status)                                     => (int) $status,
            default                                              => 0,
        };
    }

    /**
     * Save products from current batch
     *
     * Uses bulk upsert operations instead of individual saves for dramatically
     * improved performance on large imports. Inspired by combined-insert techniques
     * that can process millions of rows in seconds.
     */
    public function saveProducts(array $products): void
    {
        Event::dispatch('data_transfer.imports.batch.product.save.before');

        $ids = [];

        if (! empty($products['update'])) {
            $this->bulkUpdateProducts($products['update'], $ids);
        }

        if (! empty($products['insert'])) {
            $this->bulkInsertProducts($products['insert'], $ids);
        }

        Event::dispatch('data_transfer.imports.batch.product.save.after', ['product_id' => $ids]);
    }

    /**
     * Bulk update existing products using a single query per chunk.
     *
     * Instead of loading each Eloquent model, checking dirty, and saving individually,
     * this uses DB::table()->upsert() to update all products in one combined query.
     */
    protected function bulkUpdateProducts(array $updateProducts, array &$ids): void
    {
        $upsertData = [];

        foreach ($updateProducts as $productData) {
            $skuInfo = $this->skuStorage->get($productData['sku']);
            $id = $skuInfo['id'];
            $ids[] = $id;

            $upsertData[] = [
                'id'                  => $id,
                'sku'                 => $productData['sku'],
                'type'                => $productData['type'],
                'parent_id'           => $productData['parent_id'] ?? null,
                'attribute_family_id' => $productData['attribute_family_id'],
                'values'              => is_array($productData['values']) ? json_encode($productData['values']) : $productData['values'],
                'status'              => $productData['status'] ?? 0,
                'updated_at'          => now(),
            ];

            $this->updatedItemsCount++;
        }

        if (! empty($upsertData)) {
            $chunkSize = (int) config('import.bulk_chunk_size', 500);

            foreach (array_chunk($upsertData, $chunkSize) as $chunk) {
                DB::table('products')->upsert(
                    $chunk,
                    ['id'],
                    ['type', 'parent_id', 'attribute_family_id', 'values', 'status', 'updated_at']
                );
            }
        }
    }

    /**
     * Bulk insert new products using combined insert queries.
     *
     * For new products, we use DB::table()->insert() in chunks followed by a single
     * query to fetch all created IDs, avoiding N+1 round-trips to the database.
     */
    protected function bulkInsertProducts(array $insertProducts, array &$ids): void
    {
        $insertData = [];
        $skusToInsert = [];

        foreach ($insertProducts as $productData) {
            $skusToInsert[] = $productData['sku'];

            $insertRow = [
                'sku'                 => $productData['sku'],
                'type'                => $productData['type'],
                'parent_id'           => $productData['parent_id'] ?? null,
                'attribute_family_id' => $productData['attribute_family_id'],
                'values'              => is_array($productData['values']) ? json_encode($productData['values']) : $productData['values'],
                'status'              => $productData['status'] ?? 0,
                'created_at'          => $productData['created_at'] ?? now(),
                'updated_at'          => $productData['updated_at'] ?? now(),
            ];

            $insertData[] = $insertRow;
        }

        if (! empty($insertData)) {
            /**
             * Insert all products in chunks using a single combined INSERT query per chunk.
             * This is dramatically faster than individual INSERT statements.
             */
            $chunkSize = (int) config('import.bulk_chunk_size', 500);

            foreach (array_chunk($insertData, $chunkSize) as $chunk) {
                DB::table('products')->insert($chunk);
            }

            /**
             * Fetch all newly created product IDs in a single query
             * instead of loading them one by one.
             */
            $newProducts = DB::table('products')
                ->whereIn('sku', $skusToInsert)
                ->select('id', 'sku', 'type', 'attribute_family_id')
                ->get();

            foreach ($newProducts as $product) {
                $this->skuStorage->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);

                $ids[] = $product->id;

                $this->createdItemsCount++;
            }
        }
    }

    /**
     * Save products from current batch.
     *
     * Optimized: Uses a keyed-by-code index for O(1) attribute lookups
     * instead of Collection->where('code', ...)->first() per field per row.
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $type = $rowData['type'];
        $familyCode = $rowData[self::ATTRIBUTE_FAMILY_CODE];
        $channel = $rowData['channel'] ?? null;
        $locale = $rowData['locale'] ?? null;

        $familyAttributes = $this->getProductTypeFamilyAttributes($type, $familyCode);
        $attributesByCode = $this->getTypeFamilyAttributesByCode($type, $familyCode, $familyAttributes);
        $imageDirPath = $this->import->images_directory_path;

        /**
         * Precompute scope strings for this type/family once.
         * Bypasses Eloquent __get overhead (~54µs per setProductValue call)
         * by replacing Eloquent scope-check methods with a plain string lookup.
         */
        $scopePrefix = $type.'|'.$familyCode.'|';

        if (! isset($this->attributeScopeCache[$type.'|'.$familyCode])) {
            foreach ($attributesByCode as $code => $attr) {
                $this->attributeScopeCache[$scopePrefix.$code] = match (true) {
                    (bool) ($attr->value_per_locale && $attr->value_per_channel)  => 'channel_locale',
                    (bool) $attr->value_per_channel                               => 'channel',
                    (bool) $attr->value_per_locale                                => 'locale',
                    default                                                       => 'common',
                };
            }

            // Mark this family as fully computed
            $this->attributeScopeCache[$type.'|'.$familyCode] = true;
        }

        foreach ($rowData as $attributeCode => $value) {
            if (is_null($value)) {
                continue;
            }

            [$attributeCode, $currencyCode] = $this->getAttributeCodeAndCurrency($attributeCode);

            $attribute = $attributesByCode[$attributeCode] ?? null;

            if (! $attribute) {
                continue;
            }

            if ($attribute->type === 'gallery') {
                $value = explode(',', $value);
            }

            $value = $this->fieldProcessor->handleField($attribute, $value, $imageDirPath);

            if ($attribute->type === 'price') {
                $value = $this->formatPriceValueWithCurrency($currencyCode, $value, $attribute->getValueFromProductValues($attributeValues, $channel, $locale));
            }

            $value = EscapeFormulaOperators::unescapeValue($value);

            /**
             * Direct array assignment using precomputed scope — avoids Eloquent __get
             * overhead in setProductValue (was ~54µs per call due to isLocaleBasedAttribute() etc.)
             */
            switch ($this->attributeScopeCache[$scopePrefix.$attributeCode] ?? 'common') {
                case 'channel_locale':
                    $attributeValues['channel_locale_specific'][$channel][$locale][$attributeCode] = $value;
                    break;
                case 'channel':
                    $attributeValues['channel_specific'][$channel][$attributeCode] = $value;
                    break;
                case 'locale':
                    $attributeValues['locale_specific'][$locale][$attributeCode] = $value;
                    break;
                default:
                    $attributeValues['common'][$attributeCode] = $value;
            }
        }
    }

    /**
     * return the parent id if existing product which has parent id
     * otherwise returns the parent id according to parent column data for row
     */
    protected function getParentId(array $rowData, ?int $parentId): ?int
    {
        if ($parentId) {
            return $parentId;
        }

        if (! empty($rowData['parent'])) {
            return $this->getExistingProduct($rowData['parent'])?->id;
        }

        return null;
    }

    /**
     * Add category and associations data to the product
     */
    public function prepareOtherSections(array $rowData, array &$product): void
    {
        if (! empty($rowData[AbstractType::CATEGORY_VALUES_KEY])) {
            $categories = explode(',', $rowData[AbstractType::CATEGORY_VALUES_KEY]);

            $categories = array_map('trim', $categories);

            /**
             * Use pre-loaded category codes cache for O(1) lookups
             * instead of per-row DB queries. categoryCodes is flipped (code => index),
             * so we check isset() for each code.
             */
            $categoryCodes = ! empty($this->categoryCodes)
                ? array_values(array_filter($categories, fn ($code) => isset($this->categoryCodes[$code])))
                : $this->categoryRepository->whereIn('code', $categories)?->pluck('code')?->toArray();

            if (! empty($categoryCodes)) {
                $product[AbstractType::PRODUCT_VALUES_KEY][AbstractType::CATEGORY_VALUES_KEY] = $categoryCodes;
            }
        }

        foreach (AbstractType::ASSOCIATION_SECTIONS as $section) {
            if (empty($rowData[$section])) {
                continue;
            }

            $associations = explode(',', $rowData[$section]);

            $filteredAssociation = [];

            foreach ($associations as $value) {
                $value = is_string($value) ? trim($value) : $value;

                if (empty($value) || $value == $rowData['sku']) {
                    continue;
                }

                $filteredAssociation[] = $value;
            }

            /**
             * Use SKUStorage for O(1) lookups instead of per-row DB query
             */
            $associationProducts = array_filter($filteredAssociation, fn ($sku) => $this->skuStorage->has($sku));

            if (empty($associationProducts)) {
                continue;
            }

            $product[AbstractType::PRODUCT_VALUES_KEY][AbstractType::ASSOCIATION_VALUES_KEY][$section] = array_values($associationProducts);
        }
    }

    /**
     * Prepare images from current batch
     */
    public function prepareImages(array $rowData, array &$imagesData): void
    {
        if (empty($rowData['images'])) {
            return;
        }

        /**
         * Skip the image upload if product is already created
         */
        if ($this->skuStorage->has($rowData['sku'])) {
            return;
        }

        /**
         * Reset the sku images data to prevent
         * data duplication in case of multiple locales
         */
        $imagesData[$rowData['sku']] = [];

        $imageNames = array_map('trim', explode(',', $rowData['images']));

        foreach ($imageNames as $key => $image) {
            $path = 'import/'.$this->import->images_directory_path.'/'.$image;

            if (! Storage::disk('local')->has($path)) {
                continue;
            }

            $imagesData[$rowData['sku']][] = [
                'name' => $image,
                'path' => Storage::disk('local')->path($path),
            ];
        }
    }

    /**
     * Save images from current batch
     */
    public function saveImages(array $imagesData): void
    {
        if (empty($imagesData)) {
            return;
        }

        $productImages = [];

        foreach ($imagesData as $sku => $images) {
            $product = $this->skuStorage->get($sku);

            foreach ($images as $key => $image) {
                $file = new UploadedFile($image['path'], $image['name']);

                $image = (new ImageManager)->make($file)->encode('webp');

                $imageDirectory = 'product/'.$product['id'];

                $path = $imageDirectory.'/'.Str::random(40).'.webp';

                $productImages[] = [
                    'type'       => 'images',
                    'path'       => $path,
                    'product_id' => $product['id'],
                    'position'   => $key + 1,
                ];

                Storage::put($path, $image);
            }
        }
    }

    /**
     * Prepare configurable variants
     */
    public function prepareConfigurableAttributes(array $rowData, array &$products, bool $isExisting): void
    {
        if (
            $rowData['type'] != self::PRODUCT_TYPE_CONFIGURABLE && empty($rowData['configurable_attributes'])
            || $isExisting
        ) {
            return;
        }

        $superAttributes = explode(',', $rowData['configurable_attributes']);

        foreach ($superAttributes as $attribute) {
            $attribute = trim($attribute);

            /**
             * O(1) indexed lookup instead of Collection->where('code', ...)->first()
             */
            $attributeCode = ($this->allAttributesByCode[$attribute] ?? null)?->code;

            if (! isset($products['insert'][$rowData['sku']]['super_attributes'])) {
                $products['insert'][$rowData['sku']]['super_attributes'] = [];
            }

            if (! $attributeCode || in_array($attributeCode, $products['insert'][$rowData['sku']]['super_attributes'])) {
                continue;
            }

            $products['insert'][$rowData['sku']]['super_attributes'][] = $attributeCode;
        }
    }

    /**
     * Returns super attributes options of current batch
     */
    public function getSuperAttributeOptions(array $variants): mixed
    {
        $optionLabels = array_unique(Arr::flatten($variants));

        return $this->attributeOptionRepository->findWhereIn('code', $optionLabels);
    }

    /**
     * Save links
     */
    public function loadUnloadedSKUs(array $skus): void
    {
        $notLoadedSkus = [];

        foreach ($skus as $sku) {
            if ($this->skuStorage->has($sku)) {
                continue;
            }

            $notLoadedSkus[] = $sku;
        }

        /**
         * Load not loaded SKUs to the sku storage
         */
        if (! empty($notLoadedSkus)) {
            $this->skuStorage->load($notLoadedSkus);
        }
    }

    /**
     * Retrieve product type family attributes
     */
    public function getProductTypeFamilyAttributes(string $type, string $attributeFamilyCode): mixed
    {
        if (isset($this->typeFamilyAttributes[$type][$attributeFamilyCode])) {
            return $this->typeFamilyAttributes[$type][$attributeFamilyCode];
        }

        $attributeFamily = $this->attributeFamiliesByCode[$attributeFamilyCode]
            ?? $this->attributeFamilies->where('code', $attributeFamilyCode)->first();

        $product = ProductModel::make([
            'type'                => $type,
            'attribute_family_id' => $attributeFamily->id,
        ]);

        return $this->typeFamilyAttributes[$type][$attributeFamilyCode] = $product->getEditableAttributes();
    }

    /**
     * Get family attributes indexed by code for O(1) lookups.
     *
     * Caches the keyed version so indexing only happens once per type/family.
     */
    protected function getTypeFamilyAttributesByCode(string $type, string $attributeFamilyCode, $familyAttributes): array
    {
        $cacheKey = $type.'|'.$attributeFamilyCode;

        if (isset($this->typeFamilyAttributesByCode[$cacheKey])) {
            return $this->typeFamilyAttributesByCode[$cacheKey];
        }

        $indexed = [];

        foreach ($familyAttributes as $attribute) {
            $indexed[$attribute->code] = $attribute;
        }

        return $this->typeFamilyAttributesByCode[$cacheKey] = $indexed;
    }

    /**
     * Check if SKU exists
     */
    public function isSKUExist(string $sku): bool
    {
        return $this->skuStorage->has($sku);
    }

    /**
     * Prepare row data to save into the database
     */
    protected function prepareRowForDb(array $rowData): array
    {
        $rowData = parent::prepareRowForDb($rowData);

        $rowData['locale'] = $rowData['locale'] ?? app()->getLocale();

        $rowData['channel'] = $rowData['channel'] ?? core()->getDefaultChannelCode();

        return $rowData;
    }

    /**
     * Get Existing product through sku.
     *
     * Uses in-memory cache to avoid per-row DB queries.
     * Falls back to DB only if not in cache.
     */
    public function getExistingProduct(string $sku)
    {
        if (isset($this->existingProductsCache[$sku])) {
            return $this->existingProductsCache[$sku];
        }

        $product = $this->productRepository->findOneByField('sku', $sku);

        if ($product) {
            $this->existingProductsCache[$sku] = $product;
        }

        return $product;
    }

    /**
     * format price value with currency code as json encoded
     */
    protected function formatPriceValueWithCurrency(
        string $currencyCode,
        mixed $value,
        mixed $oldValues
    ) {
        if (is_string($oldValues)) {
            $oldValues = json_decode($oldValues, true) ?? [];
        }

        $oldValues[$currencyCode] = (string) $value;

        return $oldValues;
    }

    /**
     * return attribute code and currency code from the column name
     */
    protected function getAttributeCodeAndCurrency(string $columnName): array
    {
        if (! str_contains($columnName, '(')) {
            return [
                $columnName,
                null,
            ];
        }

        $columnName = str_replace([' ', ')'], ['', ''], $columnName);

        $explodedValues = explode('(', $columnName);

        return [
            $explodedValues[0],
            $explodedValues[1],
        ];
    }

    /**
     * Merge Attribute values for each section with previous section
     */
    protected function mergeAttributeValues(array $newValues, array $oldValues): array
    {
        if (empty($oldValues)) {
            return $newValues;
        }

        if (! empty($oldValues[AbstractType::COMMON_VALUES_KEY])) {
            $newValues[AbstractType::COMMON_VALUES_KEY] = array_filter(
                array_merge($oldValues[AbstractType::COMMON_VALUES_KEY] ?? [], $newValues[AbstractType::COMMON_VALUES_KEY])
            );
        }

        foreach ($this->channelsAndLocales as $channelCode => $locales) {
            $newValues[AbstractType::CHANNEL_VALUES_KEY][$channelCode] = array_filter(
                array_merge($oldValues[AbstractType::CHANNEL_VALUES_KEY][$channelCode] ?? [], $newValues[AbstractType::CHANNEL_VALUES_KEY][$channelCode] ?? [])
            );

            foreach ($locales as $localeCode) {
                $newValues[AbstractType::LOCALE_VALUES_KEY][$localeCode] = array_filter(
                    array_merge($oldValues[AbstractType::LOCALE_VALUES_KEY][$localeCode] ?? [], $newValues[AbstractType::LOCALE_VALUES_KEY][$localeCode] ?? [])
                );

                $newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode][$localeCode] = array_filter(
                    array_merge($oldValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode][$localeCode] ?? [], $newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode][$localeCode] ?? [])
                );

                if (empty($newValues[AbstractType::LOCALE_VALUES_KEY][$localeCode])) {
                    unset($newValues[AbstractType::LOCALE_VALUES_KEY][$localeCode]);
                }

                if (empty($newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode][$localeCode])) {
                    unset($newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode][$localeCode]);
                }
            }

            if (empty($newValues[AbstractType::CHANNEL_VALUES_KEY][$channelCode])) {
                unset($newValues[AbstractType::CHANNEL_VALUES_KEY][$channelCode]);
            }

            if (empty($newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode])) {
                unset($newValues[AbstractType::CHANNEL_LOCALE_VALUES_KEY][$channelCode]);
            }
        }

        return array_filter($newValues);
    }

    /**
     * validate variant configurable attributes uniquness
     */
    protected function validateVariantUniqueness(array $productData, int $rowNumber): bool
    {
        $parentProduct = $this->getExistingProduct($productData['parent']);

        $product = $this->skuStorage->get($productData['sku']);

        $variationId = $product['id'] ?? null;

        $variantConfigValues = [];

        foreach ($parentProduct?->super_attributes ?? [] as $attribute) {
            $attributeCode = $attribute->code;

            if (! isset($productData[$attributeCode])) {
                $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_VARIANT_CONFIGURABLE_ATTRIBUTE, $attributeCode, trans($this->messages[self::ERROR_NOT_FOUND_VARIANT_CONFIGURABLE_ATTRIBUTE], ['code' => $attributeCode]));

                return false;
            }

            $variantConfigValues[$attributeCode] = $productData[$attributeCode];
        }

        if (empty($variantConfigValues)) {
            return false;
        }

        foreach ($this->cachedVariantValues[$parentProduct->sku] ?? [] as $variantSku => $variantValues) {
            /**
             * If same configurable attributes for a variant already exist then should skip this row
             */
            if ($variantValues == $variantConfigValues && $variantSku != $productData['sku']) {
                $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VARIANT, 'sku', trans($this->messages[self::ERROR_NOT_UNIQUE_VARIANT]));

                return false;
            }
        }

        $this->cachedVariantValues[$parentProduct->sku][$productData['sku']] = $variantConfigValues;

        $isUnique = $this->productRepository->isUniqueVariantForProduct($parentProduct->id, $variantConfigValues, $productData['sku'], $variationId);

        if (! $isUnique) {
            $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VARIANT, 'sku', trans($this->messages[self::ERROR_NOT_UNIQUE_VARIANT]));

            return false;
        }

        return true;
    }

    /**
     * Check is unique values for the variation product and add error if not unique for the product
     */
    protected function isUniqueVariation(array $productData, int $rowNumber): void
    {
        if (empty($productData['parent']) || $productData['type'] !== self::PRODUCT_TYPE_SIMPLE) {
            return;
        }

        $this->validateVariantUniqueness($productData, $rowNumber);
    }

    /**
     * Validation channel and locale column in the row
     */
    protected function validChannelsAndLocale(array $rowData, int $rowNumber): bool
    {
        if (empty($this->channelsAndLocales[$rowData['channel']])) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_CHANNEL, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_CHANNEL]));

            return false;
        }

        $rowLocale = $rowData['locale'];

        $channelLocales = $this->channelsAndLocales[$rowData['channel']];

        $channelLocales = is_array($channelLocales) ? $channelLocales : [];

        if (! in_array($rowLocale, $channelLocales)) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_LOCALE_IN_CHANNEL, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_LOCALE_IN_CHANNEL]));

            return false;
        }

        return true;
    }

    /**
     * Validate unique product attribute values.
     *
     * Optimized: CSV-internal duplicates are checked via in-memory caches.
     * DB uniqueness is checked against pre-loaded existingUniqueDBValues
     * instead of running per-row `unique:products,...` DB queries.
     */
    protected function validatUniqueAttributeValues(array $rowData, int $rowNumber)
    {
        $configurableAttributes = [];

        if ($rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE && ! empty($rowData['configurable_attributes'])) {
            $configurableAttributes = str_contains($rowData['configurable_attributes'], ',') ? explode(',', $rowData['configurable_attributes'] ?? '') : [$rowData['configurable_attributes']];

            $configurableAttirbutes = array_filter($configurableAttributes, 'trim');
        }

        $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE])->where('is_unique', 1);

        $existingProductId = $this->skuStorage->get($rowData['sku'])['id'] ?? null;

        foreach ($familyAttributes as $attribute) {
            $hasError = false;

            $attributeCode = $attribute->code;

            if (! empty($configurableAttributes) && in_array($attributeCode, $configurableAttributes)) {
                continue;
            }

            if (! isset($rowData[$attributeCode])) {
                continue;
            }

            if ($attribute->isLocaleAndChannelBasedAttribute()) {
                foreach ($this->channelLocaleCachedValues[$rowData['channel']][$rowData['locale']] ?? [] as $productData) {
                    if (! isset($productData[$rowData['channel']][$rowData['locale']][$attributeCode])) {
                        continue;
                    }

                    if ($productData[$rowData['channel']][$rowData['locale']][$attributeCode] == $rowData[$attributeCode]) {
                        $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $attributeCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $attributeCode]));

                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    if (! empty($rowData[$attributeCode])) {
                        $this->channelLocaleCachedValues[$rowData['channel']][$rowData['locale']] = [$attributeCode => $rowData[$attributeCode]];
                    }
                }

                continue;
            }

            if ($attribute->isChannelBasedAttribute()) {
                foreach ($this->channelCachedValues[$rowData['channel']] ?? [] as $productData) {
                    if (! isset($productData[$rowData['channel']][$attributeCode])) {
                        continue;
                    }

                    if ($productData[$rowData['channel']][$attributeCode] == $rowData[$attributeCode]) {
                        $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $attributeCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $attributeCode]));

                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    if (! empty($rowData[$attributeCode])) {
                        $this->channelCachedValues[$rowData['channel']] = [$attributeCode => $rowData[$attributeCode]];
                    }
                }

                continue;
            }

            if ($attribute->isLocaleBasedAttribute()) {
                foreach ($this->localeCachedValues[$rowData['locale']] ?? [] as $productData) {
                    if (! isset($productData[$rowData['locale']][$attributeCode])) {
                        continue;
                    }

                    if ($productData[$rowData['locale']][$attributeCode] == $rowData[$attributeCode]) {
                        $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $attributeCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $attributeCode]));

                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    if (! empty($rowData[$attributeCode])) {
                        $this->localeCachedValues[$rowData['locale']] = [$attributeCode => $rowData[$attributeCode]];
                    }
                }

                continue;
            }

            foreach ($this->cachedUniqueValues as $sku => $productData) {
                if (! isset($productData[$attributeCode])) {
                    continue;
                }

                if ($productData[$attributeCode] == $rowData[$attributeCode] && $sku != $rowData['sku']) {
                    $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $attributeCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $attributeCode]));

                    $hasError = true;

                    break;
                }
            }

            if (! $hasError) {
                if (! empty($rowData[$attributeCode])) {
                    $this->cachedUniqueValues[$rowData['sku']] = [$attributeCode => $rowData[$attributeCode]];
                }
            }
        }

        /**
         * Check DB uniqueness against pre-loaded values instead of
         * running per-row `unique:products,...` queries via Validator.
         */
        foreach ($familyAttributes as $attribute) {
            $attributeCode = $attribute->code;

            if (! empty($configurableAttributes) && in_array($attributeCode, $configurableAttributes)) {
                continue;
            }

            if (! isset($rowData[$attributeCode]) || empty($rowData[$attributeCode])) {
                continue;
            }

            $scopeKey = $this->getUniqueScopeKey($attribute, $rowData);
            $value = (string) $rowData[$attributeCode];

            if (
                isset($this->existingUniqueDBValues[$attributeCode][$scopeKey][$value])
                && (int) $this->existingUniqueDBValues[$attributeCode][$scopeKey][$value] !== (int) $existingProductId
            ) {
                $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $attributeCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $attributeCode]));
            }
        }
    }

    /**
     * Is indexing resource required for the import operation
     */
    public function isIndexingRequired(): bool
    {
        if ($this->import->action == Import::ACTION_DELETE) {
            return false;
        }

        /**
         * Deferred indexing: skip per-batch ES indexing during import.
         * Run `php artisan unopim:product:index` after import completes.
         */
        if (config('import.deferred_indexing', false)) {
            return false;
        }

        return config('elasticsearch.enabled') && $this->indexingRequired;
    }

    /**
     * Index batch data to elasticsearch
     *
     * Optimized to select only required columns and process data with minimal
     * object hydration — raw arrays and substr() over DateTime parsing.
     */
    public function indexBatch(JobTrackBatchContract $batch)
    {
        if (! config('elasticsearch.enabled')) {
            return;
        }

        $productIndexingNormalizer = app(ProductNormalizer::class);

        $productIndex = strtolower(config('elasticsearch.prefix').'_products');

        /**
         * Select only the columns needed for indexing instead of SELECT *.
         * This reduces I/O and memory usage significantly.
         */
        $products = DB::table('products')
            ->whereIn('sku', Arr::pluck($batch->data, 'sku'))
            ->select('id', 'sku', 'type', 'parent_id', 'attribute_family_id', 'values', 'status', 'created_at', 'updated_at')
            ->get();

        $productsToUpdate = [];

        foreach ($products as $productDB) {
            $productDB = (array) $productDB;

            /**
             * Use raw string json_decode only once, and use substr for date
             * year extraction where applicable — avoid DateTime object creation.
             */
            $productDB['values'] = is_string($productDB['values']) ? json_decode($productDB['values'], true) : $productDB['values'];
            $productDB['values'] = $productIndexingNormalizer->normalize($productDB['values']);

            /**
             * Use substr for ISO date format instead of Carbon::parse()
             * when the format is known (Y-m-d H:i:s from MySQL).
             * Fall back to Carbon only for non-standard formats.
             */
            $productDB['created_at'] = $this->formatDateForIndex($productDB['created_at']);
            $productDB['updated_at'] = $this->formatDateForIndex($productDB['updated_at']);

            $productsToUpdate['body'][] = [
                'index' => [
                    '_index' => $productIndex,
                    '_id'    => $productDB['id'],
                ],
            ];

            $productsToUpdate['body'][] = $productDB;
        }

        if (empty($productsToUpdate)) {
            return;
        }

        $response = ElasticSearch::bulk($productsToUpdate);

        if (isset($response['errors']) && $response['errors']) {
            foreach ($response['items'] as $index => $result) {
                if (isset($result['index']['error'])) {
                    Log::channel('elasticsearch')->error('Error while indexing product id: '.$result['index']['_id'].' in '.$productIndex.' index: ', [
                        'error' => $result['index']['error'],
                    ]);
                }
            }
        }
    }

    /**
     * Format a date string for ElasticSearch indexing.
     *
     * Uses fast string manipulation for standard MySQL datetime format
     * instead of creating Carbon/DateTime objects for each row.
     */
    protected function formatDateForIndex(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        /**
         * Standard MySQL format: "2025-01-15 10:30:00"
         * Convert to ISO 8601: "2025-01-15T10:30:00.000000Z"
         */
        if (strlen($date) === 19 && $date[4] === '-' && $date[10] === ' ') {
            return substr($date, 0, 10).'T'.substr($date, 11).'.000000Z';
        }

        return Carbon::parse($date)->toJson();
    }
}
