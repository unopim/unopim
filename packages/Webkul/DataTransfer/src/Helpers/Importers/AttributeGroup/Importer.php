<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeGroup;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
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
        'locale',
        'name',
    ];

    /**
     * Current Batch Attribute Group codes
     */
    protected array $attributeGroupCodesInBatch = [];

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
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.attribute-groups.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.attribute-groups.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
        self::ERROR_CODE_IS_SYSTEM            => 'data_transfer::app.importers.attribute-groups.validation.errors.code_is_system_and_cannot_be_deleted',
    ];

    /**
     * locales storage
     */
    protected array $locales = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected Storage $attributeGroupStorage,
        protected LocaleRepository $localeRepository
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
    }

    /**
     * Initialize Attribute Group error templates
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
        $this->attributeGroupStorage->init();

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
            $id = $this->attributeGroupStorage->get($rowData['code']);

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

        $isUpdate = $this->attributeGroupStorage->has($rowData['code']) || in_array($rowData['code'], $this->attributeGroupCodesInBatch);

        $validator = Validator::make($rowData, [
            'code'     => ['required', 'string', new Code, $isUpdate ? '' : 'unique:attribute_groups,code'],
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
            $this->attributeGroupCodesInBatch[] = $rowData['code'];
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
            $this->deleteAttributeGroupData($batch);
        } else {
            $this->saveAttributeGroupData($batch);
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
     * Delete attribute groups from current batch
     */
    protected function deleteAttributeGroupData(JobTrackBatchContract $batch): bool
    {
        $this->attributeGroupStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isAttributeGroupExist($rowData['code'])) {
                continue;
            }

            $idsToDelete[] = $this->attributeGroupStorage->get($rowData['code']);
        }

        $idsToDelete = array_unique($idsToDelete);
        $this->deletedItemsCount = count($idsToDelete);

        $this->attributeGroupRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save attribute groups from current batch
     */
    protected function saveAttributeGroupData(JobTrackBatchContract $batch): bool
    {
        $codes = Arr::pluck($batch->data, 'code');
        $this->attributeGroupStorage->load($codes);

        $attributeGroups = [];

        foreach ($batch->data as $rowData) {
            $this->prepareAttributeGroups($rowData, $attributeGroups);
        }

        $this->saveAttributeGroups($attributeGroups);

        return true;
    }

    /**
     * Prepare attribute groups from current batch
     */
    public function prepareAttributeGroups(array $rowData, array &$attributeGroups): void
    {
        $isAttributeGroup = $this->isAttributeGroupExist($rowData['code']);

        $data = [
            'code'             => $rowData['code'],
            $rowData['locale'] => ['name' => $rowData['name'] ?? null],
        ];

        if ($isAttributeGroup || isset($attributeGroups['insert'][$rowData['code']])) {
            $type = $isAttributeGroup ? 'update' : 'insert';

            if (isset($attributeGroups[$type][$rowData['code']])) {
                $attributeGroups[$type][$rowData['code']] = array_replace_recursive(
                    $attributeGroups[$type][$rowData['code']],
                    $data
                );
            } else {
                $attributeGroups[$type][$rowData['code']] = $data;
            }
        } else {
            $attributeGroups['insert'][$rowData['code']] = $data;
        }
    }

    /**
     * Save attribute groups from current batch
     */
    public function saveAttributeGroups(array $attributeGroups): void
    {
        if (! empty($attributeGroups['update'])) {
            $this->updatedItemsCount += count($attributeGroups['update']);

            foreach ($attributeGroups['update'] as $code => $groupData) {
                $id = $this->attributeGroupStorage->get($code);
                $this->attributeGroupRepository->update($groupData, $id);
            }
        }

        if (! empty($attributeGroups['insert'])) {
            $this->createdItemsCount += count($attributeGroups['insert']);

            foreach ($attributeGroups['insert'] as $code => $groupData) {
                $newGroup = $this->attributeGroupRepository->create($groupData);

                if ($newGroup) {
                    $this->attributeGroupStorage->set($code, $newGroup->id);
                }
            }
        }
    }

    /**
     * Check if attribute group code exists
     */
    public function isAttributeGroupExist(string $code): bool
    {
        return $this->attributeGroupStorage->has($code);
    }
}
