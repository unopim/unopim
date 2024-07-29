<?php

namespace Webkul\DataTransfer\Helpers\Importers\Category;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

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
     * Enabled Value per locale
     */
    public const VALUE_PER_LOCALE = 1;

    /**
     * Error code for non existing code
     */
    public const ERROR_NOT_FOUND_LOCALE = 'slug_not_found_to_delete';

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
     * Category supported image types
     */
    protected array $imageTypes = [
    ];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['code', 'parent', 'locale'];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'id';

    const ERROR_NOT_UNIQUE_VALUE = 'not_unique_value';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.categories.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.categories.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
        self::ERROR_NOT_UNIQUE_VALUE          => 'data_transfer::app.importers.products.validation.errors.not-unique-value',
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

        parent::validateData();
    }

    public function getCategoryFields()
    {
        if (! isset($this->categoryFields)) {
            $this->cachedCategoryFields = $this->categoryFieldRepository->where('status', 1)->get();

            $this->categoryFields = $this->cachedCategoryFields->pluck('code')->toArray();
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
            if (! $this->isCategoryExist($rowData['code'])) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

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
         * Validate category attributes
         */
        $validator = Validator::make($rowData, [
            'code'   => 'string|required',
            'parent' => 'string',
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

        return ! $this->errorHelper->isRowInvalid($rowNumber);
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
         * Load category storage with batch code
         */
        $this->categoryStorage->load(Arr::pluck($batch->data, 'code'));

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

        $data = [
            'code'            => $rowData['code'],
            'parent'          => $rowData['parent'],
            'additional_data' => $isCategory ? $this->categoryRepository->where('code', $rowData['code'])->first()->toArray()['additional_data'] : [],
        ];

        /** additional fields data import  */
        $categoryFields = $this->getCategoryFields();
        foreach ($rowData as $field => $value) {
            if (in_array($field, $categoryFields)) {
                $catalogField = $this->categoryFieldRepository->where('code', $field)->first();

                if ($catalogField->value_per_locale === self::VALUE_PER_LOCALE) {
                    $locale = $rowData['locale'] ?? null;
                    if ($locale) {
                        $data['additional_data']['locale_specific'][$locale][$field] = $value;
                    }
                } else {
                    $data['additional_data']['common'][$field] = $value;
                }
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
     */
    public function saveCategories(array $categories): void
    {
        /** single insert/update in the db because of parent  */
        if (! empty($categories['update'])) {
            $this->updatedItemsCount += count($categories['update']);

            foreach ($categories['update'] as $code => $category) {
                $this->updateParentCategoryId($category);
                $this->categoryRepository->update($category, $this->categoryStorage->get($code), withoutFormattingValues: true);
            }
        }

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
            $category['parent_id'] = $this->getCategoryId($category['parent']);
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
                array_merge($newValues[CategoryRepository::COMMON_VALUES_KEY] ?? [], $oldValues[CategoryRepository::COMMON_VALUES_KEY])
            );
        }

        foreach ($this->locales as $localeCode) {
            $newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] = array_filter(
                array_merge($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [], $oldValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [])
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
                    $this->localeCachedValues[$rowData['locale']] = [$categoryFieldCode => $rowData[$categoryFieldCode]];

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
                $this->cachedUniqueValues[$rowData['code']] = [$categoryFieldCode => $rowData[$categoryFieldCode]];

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
}
