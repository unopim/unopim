<?php

namespace Webkul\DataTransfer\Helpers\Importers\CategoryField;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'code_not_found_to_delete';

    public const ERROR_NOT_FOUND_LOCALE = 'locale_not_exist';

    /**
     * Valid field types for category fields
     */
    public const VALID_TYPES = [
        'text', 'textarea', 'boolean', 'select', 'multiselect',
        'datetime', 'date', 'file', 'image', 'checkbox',
    ];

    /**
     * Permanent entity columns accepted from CSV
     */
    protected array $validColumnNames = [
        'code',
        'type',
        'locale',
        'name',
        'enable_wysiwyg',
        'section',
        'position',
        'status',
        'is_required',
        'is_unique',
        'validation',
        'regex_pattern',
        'value_per_locale',
        'productCounts',
    ];

    /**
     * Current batch field codes (for duplicate detection within a batch)
     */
    protected array $categoryFieldCodesInBatch = [];

    /**
     * Permanent entity columns (always required in the CSV)
     */
    protected array $permanentAttributes = ['code', 'locale'];

    /**
     * Master attribute column
     */
    protected string $masterAttributeCode = 'id';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.category-fields.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.category-fields.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
    ];

    /**
     * Active locale codes
     */
    protected array $locales = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected Storage $categoryFieldStorage,
        protected LocaleRepository $localeRepository,
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
    }

    /**
     * Register error message templates
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    /**
     * Initialize active locales
     */
    protected function initLocales(): void
    {
        $this->locales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
    }

    /**
     * Validate data before import
     */
    public function validateData(): void
    {
        $this->categoryFieldStorage->init();

        parent::validateData();
    }

    /**
     * Validates a single row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            $id = $this->categoryFieldStorage->get($rowData['code']);

            if (! $id) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            // Protect the built-in 'name' field from deletion
            if ($rowData['code'] === CategoryField::NON_DELETABLE_FIELD_CODE) {
                $this->skipRow($rowNumber, self::ERROR_DUPLICATE_CODE, $rowData['code']);

                return false;
            }

            return true;
        }

        if (empty($rowData['locale']) || ! in_array($rowData['locale'], $this->locales)) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_LOCALE, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_LOCALE]));

            return false;
        }

        $isUpdate = $this->categoryFieldStorage->has($rowData['code'])
            || in_array($rowData['code'], $this->categoryFieldCodesInBatch);

        $validator = Validator::make($rowData, [
            'code' => ['required', 'string', new Code, $isUpdate ? '' : 'unique:category_fields,code'],
            'type' => [$isUpdate ? 'nullable' : 'required', 'string', 'in:'.implode(',', self::VALID_TYPES)],
        ]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);
                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        $isValidRow = ! $this->errorHelper->isRowInvalid($rowNumber);

        if ($isValidRow) {
            $this->categoryFieldCodesInBatch[] = $rowData['code'];
        }

        return $isValidRow;
    }

    /**
     * Start the import process for a batch
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteCategoryFieldData($batch);
        } else {
            $this->saveCategoryFieldData($batch);
        }

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
     * Delete category fields from current batch
     */
    protected function deleteCategoryFieldData(JobTrackBatchContract $batch): bool
    {
        $this->categoryFieldStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isCategoryFieldExist($rowData['code'])) {
                continue;
            }

            if ($rowData['code'] === CategoryField::NON_DELETABLE_FIELD_CODE) {
                continue;
            }

            $idsToDelete[] = $this->categoryFieldStorage->get($rowData['code']);
        }

        $idsToDelete = array_unique($idsToDelete);
        $this->deletedItemsCount = count($idsToDelete);

        $this->categoryFieldRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save category fields from current batch
     */
    protected function saveCategoryFieldData(JobTrackBatchContract $batch): bool
    {
        $codes = Arr::pluck($batch->data, 'code');
        $this->categoryFieldStorage->load($codes);

        $categoryFields = [];

        foreach ($batch->data as $rowData) {
            $this->prepareCategoryFields($rowData, $categoryFields);
        }

        $this->saveCategoryFields($categoryFields);

        return true;
    }

    /**
     * Prepare category fields data structure from a single row
     */
    public function prepareCategoryFields(array $rowData, array &$categoryFields): void
    {
        $isExisting = $this->isCategoryFieldExist($rowData['code']);

        $data = [
            'code'             => $rowData['code'],
            $rowData['locale'] => ['name' => $rowData['name'] ?? null],
        ];

        $scalarFields = [
            'type', 'enable_wysiwyg', 'section', 'position', 'status',
            'is_required', 'is_unique', 'validation', 'regex_pattern',
            'value_per_locale',
        ];

        $booleanFields = ['enable_wysiwyg', 'status', 'is_required', 'is_unique', 'value_per_locale'];

        foreach ($scalarFields as $field) {
            if (isset($rowData[$field]) && $rowData[$field] !== '') {
                if (in_array($field, $booleanFields)) {
                    $data[$field] = (int) (bool) $rowData[$field];
                } elseif ($field === 'position') {
                    $data[$field] = (int) $rowData[$field];
                } else {
                    $data[$field] = $rowData[$field];
                }
            }
        }

        if ($isExisting || isset($categoryFields['insert'][$rowData['code']])) {
            $type = $isExisting ? 'update' : 'insert';

            if (isset($categoryFields[$type][$rowData['code']])) {
                $categoryFields[$type][$rowData['code']] = array_replace_recursive(
                    $categoryFields[$type][$rowData['code']],
                    $data
                );
            } else {
                $categoryFields[$type][$rowData['code']] = $data;
            }
        } else {
            $categoryFields['insert'][$rowData['code']] = $data;
        }
    }

    /**
     * Persist category fields to the database
     */
    public function saveCategoryFields(array $categoryFields): void
    {
        if (! empty($categoryFields['update'])) {
            $this->updatedItemsCount += count($categoryFields['update']);

            foreach ($categoryFields['update'] as $code => $fieldData) {
                $id = $this->categoryFieldStorage->get($code);
                $this->categoryFieldRepository->update($fieldData, $id);
            }
        }

        if (! empty($categoryFields['insert'])) {
            $this->createdItemsCount += count($categoryFields['insert']);

            foreach ($categoryFields['insert'] as $code => $fieldData) {
                $newField = $this->categoryFieldRepository->create($fieldData);

                if ($newField) {
                    $this->categoryFieldStorage->set($code, $newField->id);
                }
            }
        }
    }

    /**
     * Check if a category field with the given code exists
     */
    public function isCategoryFieldExist(string $code): bool
    {
        return $this->categoryFieldStorage->has($code);
    }
}
