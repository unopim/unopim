<?php

namespace Webkul\DataTransfer\Helpers\Importers\Attribute;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeRepository;
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

    public const ERROR_CODE_IS_SYSTEM = 'code_is_system';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'code',
        'type',
        'locale',
        'name',
        'enable_wysiwyg',
        'position',
        'swatch_type',
        'is_required',
        'is_unique',
        'validation',
        'regex_pattern',
        'value_per_locale',
        'value_per_channel',
        'is_filterable',
        'ai_translate',
        'productCounts',
    ];

    /**
     * Current Batch Attribute codes
     */
    protected array $attributeCodesInBatch = [];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['code', 'locale'];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'id';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.attributes.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.attributes.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
        self::ERROR_CODE_IS_SYSTEM            => 'data_transfer::app.importers.attributes.validation.errors.code_is_system_and_cannot_be_deleted',
    ];

    /**
     * locales storage
     */
    protected array $locales = [];

    /**
     * codes storage
     */
    protected array $codes = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeRepository $attributeRepository,
        protected Storage $attributeStorage,
        protected LocaleRepository $localeRepository
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
    }

    /**
     * Initialize Attribute error templates
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
     * Validate data before import
     */
    public function validateData(): void
    {
        $this->attributeStorage->init();

        parent::validateData();
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            $id = $this->attributeStorage->get($rowData['code']);

            if (! $id) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            if ($rowData['code'] === 'sku') {
                $this->skipRow($rowNumber, self::ERROR_CODE_IS_SYSTEM, $rowData['code']);

                return false;
            }

            return true;
        }

        if (empty($rowData['locale']) || ! in_array($rowData['locale'], $this->locales)) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_LOCALE, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_LOCALE]));

            return false;
        }

        $isUpdate = $this->attributeStorage->has($rowData['code']) || in_array($rowData['code'], $this->attributeCodesInBatch);

        $validator = Validator::make($rowData, [
            'code' => ['required', 'string', new Code],
            'type' => [$isUpdate ? 'nullable' : 'required', 'string', 'in:text,textarea,boolean,price,select,multiselect,datetime,date,checkbox,file,image,gallery'],
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
            $this->attributeCodesInBatch[] = $rowData['code'];
        }

        return $isValidRow;
    }

    /**
     * Start the import process
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        DB::beginTransaction();

        try {
            if ($batch->jobTrack->action == Import::ACTION_DELETE) {
                $this->deleteAttributeData($batch);
            } else {
                $this->saveAttributeData($batch);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
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
     * Delete attributes from current batch
     */
    protected function deleteAttributeData(JobTrackBatchContract $batch): bool
    {
        $this->attributeStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isAttributeExist($rowData['code'])) {
                continue;
            }

            if ($rowData['code'] === 'sku') {
                continue;
            }

            $idsToDelete[] = $this->attributeStorage->get($rowData['code']);
        }

        $idsToDelete = array_unique($idsToDelete);
        $this->deletedItemsCount = count($idsToDelete);

        $this->attributeRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save attribute from current batch
     */
    protected function saveAttributeData(JobTrackBatchContract $batch): bool
    {
        $attributeCodes = Arr::pluck($batch->data, 'code');
        $this->attributeStorage->load($attributeCodes);

        $attributes = [];

        foreach ($batch->data as $rowData) {
            $this->prepareAttributes($rowData, $attributes);
        }

        $this->saveAttributes($attributes);

        return true;
    }

    /**
     * Prepare attributes from current batch
     */
    public function prepareAttributes(array $rowData, array &$attributes): void
    {
        $isAttribute = $this->isAttributeExist($rowData['code']);

        $data = [
            'code'             => $rowData['code'],
            $rowData['locale'] => ['name' => $rowData['name'] ?? null],
        ];

        $fields = [
            'type', 'enable_wysiwyg', 'position', 'swatch_type', 'is_required',
            'is_unique', 'validation', 'regex_pattern', 'value_per_locale',
            'value_per_channel', 'is_filterable', 'ai_translate',
        ];

        foreach ($fields as $field) {
            if (isset($rowData[$field]) && $rowData[$field] !== '') {
                if (in_array($field, ['enable_wysiwyg', 'is_required', 'is_unique', 'value_per_locale', 'value_per_channel', 'is_filterable', 'ai_translate'])) {
                    $data[$field] = (int) (bool) $rowData[$field];
                } elseif ($field === 'position') {
                    $data[$field] = (int) $rowData[$field];
                } else {
                    $data[$field] = $rowData[$field];
                }
            }
        }

        if ($isAttribute || isset($attributes['insert'][$rowData['code']])) {
            $type = $isAttribute ? 'update' : 'insert';

            if (isset($attributes[$type][$rowData['code']])) {
                $attributes[$type][$rowData['code']] = array_replace_recursive(
                    $attributes[$type][$rowData['code']],
                    $data
                );
            } else {
                $attributes[$type][$rowData['code']] = $data;
            }
        } else {
            $attributes['insert'][$rowData['code']] = $data;
        }
    }

    /**
     * Save attributes from current batch
     */
    public function saveAttributes(array $attributes): void
    {
        if (! empty($attributes['update'])) {
            $this->updatedItemsCount += count($attributes['update']);

            foreach ($attributes['update'] as $code => $attributeData) {
                $attributeId = $this->attributeStorage->get($code);

                $this->attributeRepository->update($attributeData, $attributeId);
            }
        }

        if (! empty($attributes['insert'])) {
            $this->createdItemsCount += count($attributes['insert']);

            foreach ($attributes['insert'] as $code => $attributeData) {
                $newAttribute = $this->attributeRepository->create($attributeData);

                if ($newAttribute) {
                    $this->attributeStorage->set($code, $newAttribute->id);
                }
            }
        }
    }

    /**
     * Check if attribute code exists
     */
    public function isAttributeExist(string $code): bool
    {
        return $this->attributeStorage->has($code);
    }
}
