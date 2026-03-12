<?php

namespace Webkul\DataTransfer\Helpers\Importers\Category;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Validators\Import\CategoryRulesExtractor;

class Importer extends AbstractImporter
{
    /**
     *  Error code for duplicated code
     */
    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    /**
     * Error code for non existing code
     */
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'slug_not_found_to_delete';

    /**
     * invalid display mode
     */
    public const INVALID_DISPLAY_MODE = 'invalid_display_mode';

    /**
     * Error code for non existing code
     */
    public const ERROR_NOT_FOUND_LOCALE = 'slug_not_found_to_delete';

    const ERROR_NOT_UNIQUE_VALUE = 'not_unique_value';

    const ERROR_RELATED_TO_CHANNEL = 'channel_related_category_root';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'code',
        'parent',
        'locale',
    ];

    protected array $categoryFields;

    /**
     * Current Batch Category codes
     */
    protected array $categoryCodesInBatch = [];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['code', 'parent', 'locale'];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'id';

    protected ?array $nonDeletableCategories = null;

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.categories.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.categories.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
        self::ERROR_NOT_UNIQUE_VALUE          => 'data_transfer::app.importers.products.validation.errors.not-unique-value',
        self::ERROR_RELATED_TO_CHANNEL        => 'data_transfer::app.importers.categories.validation.errors.channel-related-category-root',
    ];

    /**
     * codes storage
     */
    protected array $codes = [];

    /**
     * locales storage
     */
    protected array $locales = [];

    protected array $cachedUniqueValues = [];

    protected array $localeCachedValues = [];

    protected array $categoryFieldValidations = [];

    protected $cachedCategoryFields = [];

    /**
     * Category fields indexed by code for O(1) lookup in prepareCategories().
     * Populated when getCategoryFields() is first called.
     */
    protected array $categoryFieldsByCode = [];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected Storage $categoryStorage,
        protected AttributeRepository $attributeRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected CategoryRulesExtractor $categoryRulesExtractor,
        protected FieldProcessor $fieldProcessor
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
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
     * Initialize locales
     */
    protected function initLocales(): void
    {
        $this->locales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
    }

    /**
     * Validate data.
     */
    public function validateData(): void
    {
        $this->validColumnNames = array_merge($this->validColumnNames, $this->getCategoryFields());

        $this->categoryStorage->init();

        $this->getNonDeletableCategories();

        parent::validateData();
    }

    public function getCategoryFields()
    {
        if (! isset($this->categoryFields)) {
            $this->cachedCategoryFields = $this->categoryFieldRepository->where('status', 1)->get();

            $this->categoryFields = $this->cachedCategoryFields->pluck('code')->toArray();
            $this->categoryFieldsByCode = $this->cachedCategoryFields->keyBy('code')->all();
        }

        return $this->categoryFields;
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
            $id = $this->categoryStorage->get($rowData['code']);

            if (! $id) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            if (in_array($id, $this->nonDeletableCategories)) {
                $this->skipRow($rowNumber, self::ERROR_RELATED_TO_CHANNEL, $rowData['code']);

                return false;
            }

            return true;
        }

        if (empty($rowData['locale']) || ! in_array($rowData['locale'], $this->locales)) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_LOCALE, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_LOCALE]));

            return false;
        }

        if (empty($this->categoryFieldValidations)) {
            $this->categoryFieldValidations = $this->getCategoryFieldValidations();
        }

        /**
         * Validate category attributes.
         * Parent existence is checked against the in-memory categoryStorage (already loaded
         * with all existing codes via init()) and the current batch's new codes, avoiding
         * a DB query per row (which costs O(N) queries for large imports).
         */
        $validator = Validator::make($rowData, [
            'code'   => ['string', 'required', new Code],
            'parent' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! empty($value)
                        && ! $this->categoryStorage->has($value)
                        && ! in_array($value, $this->categoryCodesInBatch)
                    ) {
                        $fail(trans('validation.exists', ['attribute' => $attribute]));
                    }
                },
            ],
            ...$this->categoryFieldValidations,
        ]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        $this->validateUniqueValues($rowData, $rowNumber);

        $isValidRow = ! $this->errorHelper->isRowInvalid($rowNumber);

        if ($isValidRow) {
            $this->categoryCodesInBatch[] = $rowData['code'];
        }

        return $isValidRow;
    }

    /**
     * Start the import process
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteCategoryData($batch);
        } else {
            $this->saveCategoryData($batch);
        }

        /**
         * Update import batch summary
         */
        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
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
     * Delete categories from current batch
     */
    protected function deleteCategoryData(JobTrackBatchContract $batch): bool
    {
        /**
         * Load categories storage with batch slugs
         */
        $this->categoryStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isCategoryExist($rowData['code'])) {
                continue;
            }

            $idsToDelete[] = $this->categoryStorage->get($rowData['code']);
        }

        $idsToDelete = array_unique($idsToDelete);

        $this->deletedItemsCount = count($idsToDelete);

        $this->categoryRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save category from current batch
     */
    protected function saveCategoryData(JobTrackBatchContract $batch): bool
    {
        /**
         * Load category storage with batch codes AND parent codes so that
         * updateParentCategoryId() can resolve parent_id from storage (O(1))
         * instead of issuing a DB query per category (O(N)).
         */
        $categoryCodes = Arr::pluck($batch->data, 'code');
        $parentCodes = array_values(array_unique(array_filter(Arr::pluck($batch->data, 'parent'))));

        $this->categoryStorage->load(array_merge($categoryCodes, $parentCodes));

        $categories = [];

        $imagesData = [];

        foreach ($batch->data as $rowData) {
            /**
             * Prepare categories for import
             */
            $this->prepareCategories($rowData, $categories);
        }

        $this->saveCategories($categories);

        return true;
    }

    /**
     * Prepare categories from current batch
     */
    public function prepareCategories(array $rowData, array &$categories): void
    {
        $isCategory = $this->isCategoryExist($rowData['code']);

        $categoryValues = $categories['update'][$rowData['code']]['additional_data'] ?? [];

        if (empty($categoryValues) && $isCategory) {
            $categoryValues = $this->categoryRepository->findOneByField('code', $rowData['code'])?->additional_data ?? [];
        }

        $data = [
            'code'            => $rowData['code'],
            'parent'          => $rowData['parent'],
            'additional_data' => $categoryValues,
        ];

        /** additional fields data import  */
        $categoryFields = $this->getCategoryFields();
        $imageDirPath = $this->import->images_directory_path ?? '';

        foreach ($rowData as $field => $value) {
            if (! in_array($field, $categoryFields)) {
                continue;
            }

            // Use pre-built code-keyed map instead of a DB query per field per row.
            $catalogField = $this->categoryFieldsByCode[$field] ?? null;

            if (! $catalogField) {
                continue;
            }

            $value = $this->fieldProcessor->handleField($catalogField, $value, $imageDirPath);

            $value = EscapeFormulaOperators::unescapeValue($value);

            if ($catalogField->value_per_locale) {
                $locale = $rowData['locale'] ?? null;
                if ($locale) {
                    $data['additional_data']['locale_specific'][$locale][$field] = $value;
                }
            } else {
                $data['additional_data']['common'][$field] = $value;
            }
        }

        if ($this->isCategoryExist($rowData['code'])) {
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'], $categories['update'][$rowData['code']]['additional_data'] ?? []);

            $categories['update'][$rowData['code']] = array_merge($categories['update'][$rowData['code']] ?? [], $data);
        } else {
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'], $categories['insert'][$rowData['code']]['additional_data'] ?? []);

            $categories['insert'][$rowData['code']] = array_merge($categories['insert'][$rowData['code']] ?? [], $data);
        }
    }

    /**
     * Get the local id using code
     */
    protected function getLocalId($localeCode)
    {
        return DB::table('locales')->where('code', $localeCode)->first()?->id;
    }

    /**
     * Get category Id by code
     */
    public function getCategoryId(?string $code)
    {
        if (! $code) {
            throw new \Exception('category code not found');
        }

        return $this->categoryRepository
            ->where('code', $code)
            ->first()?->id;
    }

    /**
     * Save categories from current batch
     *
     * Uses bulk operations for updates where possible, while maintaining
     * sequential inserts for parent-child relationship integrity (nested-set).
     */
    public function saveCategories(array $categories): void
    {
        /** Bulk update existing categories using a single query per chunk */
        if (! empty($categories['update'])) {
            $this->updatedItemsCount += count($categories['update']);

            $updateData = [];

            foreach ($categories['update'] as $code => $category) {
                $this->updateParentCategoryId($category);
                $categoryId = $this->categoryStorage->get($code);

                $updateData[] = [
                    'id'              => $categoryId,
                    'code'            => $category['code'],
                    'parent_id'       => $category['parent_id'] ?? null,
                    'additional_data' => is_array($category['additional_data']) ? json_encode($category['additional_data']) : $category['additional_data'],
                    'updated_at'      => now(),
                ];
            }

            if (! empty($updateData)) {
                $chunkSize = (int) config('import.bulk_chunk_size', 500);

                foreach (array_chunk($updateData, $chunkSize) as $chunk) {
                    DB::table('categories')->upsert(
                        $chunk,
                        ['id'],
                        ['code', 'parent_id', 'additional_data', 'updated_at']
                    );
                }
            }
        }

        /**
         * Sequential inserts are required for categories because nested-set
         * (lft/rgt) values must be calculated in order for parent-child relationships.
         */
        if (! empty($categories['insert'])) {
            $this->createdItemsCount += count($categories['insert']);

            foreach ($categories['insert'] as $code => $category) {
                $this->updateParentCategoryId($category);
                $newCategory = $this->categoryRepository->create($category, withoutFormattingValues: true);

                if ($newCategory) {
                    $this->categoryStorage->set($code, $newCategory?->id);
                }
            }
        }
    }

    public function updateParentCategoryId(&$category)
    {
        if (! empty($category['parent'])) {
            // Use in-memory storage (pre-loaded with batch + parent codes) instead of
            // issuing a DB query per category. Falls back to DB only for edge cases where
            // the parent was not in the batch or found in the initial storage load.
            $category['parent_id'] = $this->categoryStorage->get($category['parent'])
                ?? $this->getCategoryId($category['parent']);
        }

        unset($category['parent']);
    }

    /**
     * Check if category code exists
     */
    public function isCategoryExist(string $code): bool
    {
        return $this->categoryStorage->has($code);
    }

    /**
     * Merge Attribute values for each section with previous section
     */
    protected function mergeCategoryFieldValues(array $newValues, array $oldValues): array
    {
        if (! empty($oldValues[CategoryRepository::COMMON_VALUES_KEY])) {
            $newValues[CategoryRepository::COMMON_VALUES_KEY] = array_filter(
                array_merge($oldValues[CategoryRepository::COMMON_VALUES_KEY] ?? [], $newValues[CategoryRepository::COMMON_VALUES_KEY])
            );
        }

        foreach ($this->locales as $localeCode) {
            $newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] = array_filter(
                array_merge($oldValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [], $newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [])
            );

            if (empty($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode])) {
                unset($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode]);
            }
        }

        return array_filter($newValues);
    }

    /**
     * Get all category fields validations
     */
    protected function getCategoryFieldValidations(): array
    {
        $validations = [];
        foreach ($this->cachedCategoryFields as $categoryField) {
            $fieldValidation = $categoryField->getValidationRules(withUniqueValidation: false);

            $fieldValidation = array_merge($fieldValidation, $this->categoryRulesExtractor->getFieldTypeRules($categoryField));

            if (empty($fieldValidation)) {
                continue;
            }

            $validations[$categoryField->code] = $fieldValidation;
        }

        return $validations;
    }

    /**
     * Validate unique product attribute values
     */
    protected function validateUniqueValues(array $rowData, int $rowNumber)
    {
        $existingCategoryId = $this->categoryStorage->get($rowData['code']) ?? null;

        $validations = [];

        $uniqueFields = $this->cachedCategoryFields->where('is_unique', 1);

        foreach ($uniqueFields as $field) {
            $hasError = false;

            $categoryFieldCode = $field->code;

            if (! isset($rowData[$categoryFieldCode])) {
                continue;
            }

            if ($field->isLocaleBasedfield()) {
                foreach ($this->localeCachedValues[$rowData['locale']] ?? [] as $categoryData) {
                    if (! isset($categoryData[$rowData['locale']][$categoryFieldCode])) {
                        continue;
                    }

                    if ($categoryData[$rowData['locale']][$categoryFieldCode] == $rowData[$categoryFieldCode]) {
                        $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $categoryFieldCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $categoryFieldCode]));

                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    if (! empty($rowData[$categoryFieldCode])) {
                        $this->localeCachedValues[$rowData['locale']] = [$categoryFieldCode => $rowData[$categoryFieldCode]];
                    }

                    $validations[$categoryFieldCode] = $field->getValidationRules($rowData['locale'], $existingCategoryId);
                }

                continue;
            }

            foreach ($this->cachedUniqueValues as $categoryCode => $categoryData) {
                if (! isset($categoryData[$categoryFieldCode])) {
                    continue;
                }

                if ($categoryData[$categoryFieldCode] == $rowData[$categoryFieldCode] && $rowData['code'] != $categoryCode) {
                    $this->skipRow($rowNumber, self::ERROR_NOT_UNIQUE_VALUE, $categoryFieldCode, trans($this->messages[self::ERROR_NOT_UNIQUE_VALUE], ['code' => $categoryFieldCode]));

                    $hasError = true;

                    break;
                }
            }

            if (! $hasError) {
                if (! empty($rowData[$categoryFieldCode])) {
                    $this->cachedUniqueValues[$rowData['code']] = [$categoryFieldCode => $rowData[$categoryFieldCode]];
                }

                $validations[$categoryFieldCode] = $field->getValidationRules($rowData['locale'], $existingCategoryId);
            }
        }

        if (empty($validations)) {
            return;
        }

        $validator = Validator::make($rowData, $validations);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $categoryFieldCode => $message) {
                $errorCode = array_key_first($failedAttributes[$categoryFieldCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $categoryFieldCode, current($message));
            }
        }
    }

    /**
     * Get Categories linked to channel which should not be deleted
     */
    public function getNonDeletableCategories(): void
    {
        if (! $this->nonDeletableCategories) {
            $this->nonDeletableCategories = $this->channelRepository->pluck('root_category_id')->toArray();
        }
    }
}
