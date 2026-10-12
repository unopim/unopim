<?php

namespace Webkul\DataTransfer\Helpers\Importers\Currency;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    /**
     * Error code for duplicated code
     */
    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    /**
     * Error code for non existing code
     */
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'code_not_found_to_delete';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'code',
        'name',
        'symbol',
        'decimal',
        'status',
    ];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['code'];

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'id';

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.currencies.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.currencies.validation.errors.code_not_found_to_delete',
    ];

    /**
     * Create a new helper instance.
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CurrencyRepository $currencyRepository,
        protected Storage $currencyStorage
    ) {
        parent::__construct($importBatchRepository);
    }

    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    public function validateData(): void
    {
        Log::info('Currency Importer Valid Columns:', $this->getValidColumnNames());
        Log::info('Source Column Names:', $this->getSource()->getColumnNames());

        $this->currencyStorage->init();

        parent::validateData();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->currencyStorage->has($rowData['code'])) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            return true;
        }

        $validator = Validator::make($rowData, [
            'code'    => ['string', 'required'],
            'name'    => ['nullable', 'string'],
            'symbol'  => ['nullable', 'string'],
            'decimal' => ['nullable', 'numeric'],
            'status'  => ['nullable', 'in:0,1,Active,Inactive,active,inactive,Enable,Disable,enable,disable'],
        ]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($this->import->action == Import::ACTION_DELETE) {
            $this->deleteCurrencyData($batch);
        } else {
            $this->saveCurrencyData($batch);
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

    protected function deleteCurrencyData(JobTrackBatchContract $batch): bool
    {
        $codes = Arr::pluck($batch->data, 'code');
        $this->currencyStorage->load($codes);

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->currencyStorage->has($rowData['code'])) {
                continue;
            }

            $idsToDelete[] = $this->currencyStorage->get($rowData['code']);
        }

        $this->deletedItemsCount = count($idsToDelete);

        $this->currencyRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    protected function saveCurrencyData(JobTrackBatchContract $batch): bool
    {
        $this->currencyStorage->load(Arr::pluck($batch->data, 'code'));

        $statusFilter = $this->import->jobInstance->filters['status'] ?? 'all';

        foreach ($batch->data as $rowData) {
            $status = $this->resolveStatus($rowData['status'] ?? 1);

            Log::info('Import Row Status Check:', [
                'code'          => $rowData['code'],
                'row_status'    => $status,
                'status_filter' => $statusFilter,
                'will_skip'     => ($statusFilter === 'enable' && ! $status),
            ]);

            if ($statusFilter === 'enable' && ! $status) {
                continue;
            }

            if ($this->currencyStorage->has($rowData['code'])) {
                $this->currencyRepository->update([
                    'symbol'  => $rowData['symbol'] ?? null,
                    'decimal' => $rowData['decimal'] ?? null,
                    'status'  => $status,
                ], $this->currencyStorage->get($rowData['code']));

                $this->updatedItemsCount++;
            } else {
                $currency = $this->currencyRepository->create([
                    'code'    => $rowData['code'],
                    'symbol'  => $rowData['symbol'] ?? null,
                    'decimal' => $rowData['decimal'] ?? null,
                    'status'  => $status,
                ]);

                $this->currencyStorage->set($rowData['code'], $currency->id);

                $this->createdItemsCount++;
            }
        }

        return true;
    }

    protected function resolveStatus(mixed $status): int
    {
        if (is_numeric($status)) {
            return (int) $status;
        }

        $status = strtolower((string) $status);

        if (in_array($status, ['active', 'enable', '1'])) {
            return 1;
        }

        return 0;
    }
}
