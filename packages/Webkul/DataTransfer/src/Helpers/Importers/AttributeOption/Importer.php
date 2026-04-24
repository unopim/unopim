<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeOption;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Storage as AttributeStorage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'code_not_found_to_delete';

    public const ERROR_NOT_FOUND_LOCALE = 'locale_not_exist';

    public const ERROR_INVALID_ATTRIBUTE = 'invalid_attribute';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'attribute_code',
        'code',
        'locale',
        'label',
        'sort_order',
        'swatch_value',
    ];

    /**
     * Current Batch Attribute Option codes
     */
    protected array $attributeOptionCodesInBatch = [];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['code', 'attribute_code', 'locale'];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'id';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.attribute-options.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.attribute-options.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.attribute-options.validation.errors.locale-not-exist',
        self::ERROR_INVALID_ATTRIBUTE         => 'data_transfer::app.importers.attribute-options.validation.errors.invalid-attribute',
    ];

    /**
     * locales storage
     */
    protected array $locales = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected Storage $attributeOptionStorage,
        protected LocaleRepository $localeRepository,
        protected AttributeStorage $attributeStorage
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
    }

    /**
     * Initialize error templates
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
        $this->attributeOptionStorage->init();
        $this->attributeStorage->init();
        $this->permanentAttributes = $this->import->action == Import::ACTION_DELETE
            ? ['code']
            : ['code', 'attribute_code', 'locale'];

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
            $id = $this->attributeOptionStorage->get($rowData['code']);

            if (! $id) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            return true;
        }

        if (empty($rowData['locale']) || ! in_array($rowData['locale'], $this->locales)) {
            $this->skipRow($rowNumber, self::ERROR_NOT_FOUND_LOCALE, 'locale', trans($this->messages[self::ERROR_NOT_FOUND_LOCALE]));

            return false;
        }

        if (empty($rowData['attribute_code']) || ! $this->attributeStorage->has($rowData['attribute_code'])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_ATTRIBUTE, 'attribute_code', trans($this->messages[self::ERROR_INVALID_ATTRIBUTE], ['code' => $rowData['attribute_code'] ?? '']));

            return false;
        }

        $isUpdate = $this->attributeOptionStorage->has($rowData['code']) || in_array($rowData['code'], $this->attributeOptionCodesInBatch);

        $codeRules = ['required', 'string', new Code];

        if (! $isUpdate) {
            $codeRules[] = 'unique:attribute_options,code';
        }

        $validator = Validator::make($rowData, [
            'code'           => $codeRules,
            'attribute_code' => ['required', 'string'],
            'sort_order'     => ['nullable', 'integer'],
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
            $this->attributeOptionCodesInBatch[] = $rowData['code'];
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
            $this->deleteAttributeOptionData($batch);
        } else {
            $this->saveAttributeOptionData($batch);
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
     * Delete attribute options from current batch
     */
    protected function deleteAttributeOptionData(JobTrackBatchContract $batch): bool
    {
        $this->attributeOptionStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isAttributeOptionExist($rowData['code'])) {
                continue;
            }

            $idsToDelete[] = $this->attributeOptionStorage->get($rowData['code']);
        }

        $idsToDelete = array_unique($idsToDelete);
        $this->deletedItemsCount = count($idsToDelete);

        $this->attributeOptionRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save attribute options from current batch
     */
    protected function saveAttributeOptionData(JobTrackBatchContract $batch): bool
    {
        $this->attributeStorage->init();

        $codes = Arr::pluck($batch->data, 'code');
        $this->attributeOptionStorage->load($codes);

        $attributeOptions = [];

        foreach ($batch->data as $rowData) {
            $this->prepareAttributeOptions($rowData, $attributeOptions);
        }

        $this->saveAttributeOptions($attributeOptions);

        return true;
    }

    /**
     * Prepare attribute options from current batch
     */
    public function prepareAttributeOptions(array $rowData, array &$attributeOptions): void
    {
        $isAttributeOption = $this->isAttributeOptionExist($rowData['code']);
        $attributeId = $this->attributeStorage->get($rowData['attribute_code']);

        if (! $attributeId) {
            return;
        }

        $data = [
            'code'             => $rowData['code'],
            'attribute_id'     => $attributeId,
            $rowData['locale'] => ['label' => $rowData['label'] ?? null],
        ];

        if (isset($rowData['sort_order']) && $rowData['sort_order'] !== '') {
            $data['sort_order'] = (int) $rowData['sort_order'];
        }

        if (isset($rowData['swatch_value'])) {
            $data['swatch_value'] = $rowData['swatch_value'];
        }

        if ($isAttributeOption || isset($attributeOptions['insert'][$rowData['code']])) {
            $type = $isAttributeOption ? 'update' : 'insert';

            if (isset($attributeOptions[$type][$rowData['code']])) {
                $attributeOptions[$type][$rowData['code']] = array_replace_recursive(
                    $attributeOptions[$type][$rowData['code']],
                    $data
                );
            } else {
                $attributeOptions[$type][$rowData['code']] = $data;
            }
        } else {
            $attributeOptions['insert'][$rowData['code']] = $data;
        }
    }

    /**
     * Save attribute options from current batch
     */
    public function saveAttributeOptions(array $attributeOptions): void
    {
        if (! empty($attributeOptions['update'])) {
            $this->updatedItemsCount += count($attributeOptions['update']);

            foreach ($attributeOptions['update'] as $code => $optionData) {
                $id = $this->attributeOptionStorage->get($code);
                $this->attributeOptionRepository->update($optionData, $id);
            }
        }

        if (! empty($attributeOptions['insert'])) {
            $this->createdItemsCount += count($attributeOptions['insert']);

            foreach ($attributeOptions['insert'] as $code => $optionData) {
                $newOption = $this->attributeOptionRepository->create($optionData);

                if ($newOption) {
                    $this->attributeOptionStorage->set($code, $newOption->id);
                }
            }
        }
    }

    /**
     * Check if attribute option code exists
     */
    public function isAttributeOptionExist(string $code): bool
    {
        return $this->attributeOptionStorage->has($code);
    }
}
