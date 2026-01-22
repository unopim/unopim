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
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
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
     * Load all attributes and families to use later
     */
    protected function initAttributes(): void
    {
        $this->attributeFamilies = $this->attributeFamilyRepository->all();

        $this->attributes = $this->attributeRepository->all();

        $this->initializeChannels();

        foreach ($this->attributes as $key => $attribute) {
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
     * Save validated batches
     */
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $source->rewind();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            $this->skuStorage->load([$rowData['sku']]);

            $this->validateRow($rowData, $source->getCurrentRowNumber());

            $source->next();
        }

        parent::saveValidatedBatches();

        return $this;
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
        if (! $this->attributeFamilies->where('code', $rowData[self::ATTRIBUTE_FAMILY_CODE])->first()) {
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
         * Validate product attributes
         */
        $validator = Validator::make($rowData, $validationRules);

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

            foreach ($attributes as $attributeCode) {
                $attributeCode = trim($attributeCode);

                $attribute = $familyAttributes->where('code', $attributeCode)->first();

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
        $mediaTypes = ['image', 'file', 'gallery'];
        $mediaAttributes = $this->attributes->whereIn('type', $mediaTypes);
        $imageDirPath = $this->import->images_directory_path ?? '';

        foreach ($mediaAttributes as $attribute) {
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

        $ids = [];

        foreach ($this->skuStorage->getItems() as $sku => $item) {
            $ids[] = $item['id'];
        }

        BulkProductCompletenessJob::dispatch($ids);

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
        /**
         * Load SKU storage with batch skus
         */
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $products = [];

        $attributeValues = [];

        $confgiurableAttributes = [];

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

        return true;
    }

    /**
     * Prepare products from current batch
     */
    public function prepareProducts(array $rowData, array &$products, ?bool $isExisting = null): void
    {
        $attributeFamilyId = $this->attributeFamilies
            ->where('code', $rowData[self::ATTRIBUTE_FAMILY_CODE])
            ->first()->id;

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
     */
    public function saveProducts(array $products): void
    {
        Event::dispatch('data_transfer.imports.batch.product.save.before');

        $ids = [];

        if (! empty($products['update'])) {
            foreach ($products['update'] as $productData) {
                $id = $this->skuStorage->get($productData['sku'])['id'];

                $ids[] = $id;

                $product = $this->productRepository->updateWithValues($productData, $id);

                $this->updatedItemsCount++;
            }
        }

        if (! empty($products['insert'])) {
            foreach ($products['insert'] as $productData) {
                $product = $this->productRepository->create($productData);

                $this->productRepository->updateWithValues($productData, $product->id);

                $this->skuStorage->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);

                $ids[] = $product->id;

                unset($product);

                $this->createdItemsCount++;
            }
        }

        Event::dispatch('data_transfer.imports.batch.product.save.after', ['product_id' => $ids]);
    }

    /**
     * Save products from current batch
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);
        $imageDirPath = $this->import->images_directory_path;

        foreach ($rowData as $attributeCode => $value) {
            if (is_null($value)) {
                continue;
            }
            /**
             * Since Price column is added like this price (USD) the below function formats and returns the actual attributeCode from the columnName
             */
            [$attributeCode, $currencyCode] = $this->getAttributeCodeAndCurrency($attributeCode);

            $attribute = $familyAttributes->where('code', $attributeCode)->first();

            if (! $attribute) {
                continue;
            }

            if ($attribute->type === 'gallery') {
                $value = explode(',', $value);
            }

            $value = $this->fieldProcessor->handleField($attribute, $value, $imageDirPath);

            if ($attribute->type === 'price') {
                $value = $this->formatPriceValueWithCurrency($currencyCode, $value, $attribute->getValueFromProductValues($attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null));
            }

            $value = EscapeFormulaOperators::unescapeValue($value);

            $attribute->setProductValue($value, $attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null);
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

            $categoryCodes = $this->categoryRepository->whereIn('code', $categories)?->pluck('code')?->toArray();

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

            $associationProducts = $this->productRepository->whereIn('sku', $filteredAssociation)?->pluck('sku')?->toArray();

            if (empty($associationProducts)) {
                continue;
            }

            $product[AbstractType::PRODUCT_VALUES_KEY][AbstractType::ASSOCIATION_VALUES_KEY][$section] = $associationProducts;
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

            $attributeCode = $this->attributes->where('code', $attribute)->first()?->code;

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

        $attributeFamily = $this->attributeFamilies->where('code', $attributeFamilyCode)->first();

        $product = ProductModel::make([
            'type'                => $type,
            'attribute_family_id' => $attributeFamily->id,
        ]);

        return $this->typeFamilyAttributes[$type][$attributeFamilyCode] = $product->getEditableAttributes();
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
     * Get Existing product through sku
     */
    public function getExistingProduct(string $sku)
    {
        return $this->productRepository->findOneByField('sku', $sku);
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
     * Validate unique product attribute values
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

        $validations = [];

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

                    $validations[$attributeCode] = $attribute->getValidationRules($rowData['channel'], $rowData['locale'], $existingProductId);
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

                    $validations[$attributeCode] = $attribute->getValidationRules($rowData['channel'], $rowData['locale'], $existingProductId);
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

                    $validations[$attributeCode] = $attribute->getValidationRules($rowData['channel'], $rowData['locale'], $existingProductId);
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

                $validations[$attributeCode] = $attribute->getValidationRules($rowData['channel'], $rowData['locale'], $existingProductId);
            }
        }

        if (empty($validations)) {
            return;
        }

        $validator = Validator::make($rowData, $validations);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
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

        return config('elasticsearch.enabled') && $this->indexingRequired;
    }

    /**
     * Index batch data to elasticsearch
     */
    public function indexBatch(JobTrackBatchContract $batch)
    {
        if (! config('elasticsearch.enabled')) {
            return;
        }

        $productIndexingNormalizer = app(ProductNormalizer::class);

        $productIndex = strtolower(config('elasticsearch.prefix').'_products');

        $products = DB::table('products')->whereIn('sku', Arr::pluck($batch->data, 'sku'))->get();

        $productsToUpdate = [];

        foreach ($products as $productDB) {
            $productDB = (array) $productDB;

            $productDB['values'] = is_string($productDB['values']) ? json_decode($productDB['values'], true) : $productDB['values'];
            $productDB['values'] = $productIndexingNormalizer->normalize($productDB['values']);

            $productDB['created_at'] = Carbon::parse($productDB['created_at'])->toJson();
            $productDB['updated_at'] = Carbon::parse($productDB['updated_at'])->toJson();

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
}
