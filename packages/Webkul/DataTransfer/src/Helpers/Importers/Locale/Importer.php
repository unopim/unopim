<?php

namespace Webkul\DataTransfer\Helpers\Importers\Locale;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    /**
     * Error code for duplicated locale code.
     */
    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    /**
     * Error code for locale not found during delete.
     */
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'locale_code_not_found_to_delete';

    /**
     * Error code for an invalid status value.
     */
    public const ERROR_INVALID_STATUS = 'invalid_status';

    /**
     * Columns the CSV/XLS file must contain.
     */
    protected array $permanentAttributes = ['code'];

    /**
     * All allowed column names in the import file.
     *
     * `status` is optional — when absent it defaults to enabled (1).
     */
    protected array $validColumnNames = [
        'code',
        'name',
        'status',
    ];

    /**
     * Error message translations.
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.locales.validation.errors.duplicate-code',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.locales.validation.errors.code-not-found-to-delete',
        self::ERROR_INVALID_STATUS            => 'data_transfer::app.importers.locales.validation.errors.invalid-status',
    ];

    protected array $codesSeenInBatch = [];

    /**
     * In-memory map of existing locale codes → ids loaded from the DB.
     *
     * @var array<string, int>
     */
    protected array $existingLocales = [];

    /**
     * Create a new helper instance.
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected LocaleRepository $localeRepository,
    ) {
        parent::__construct($importBatchRepository);
    }

    /**
     * Initialize additional error message templates.
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    /**
     * Validate a single row from the source file.
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        $code = $rowData['code'] ?? null;

        /**
         * Delete action: code must already exist in DB.
         */
        if ($this->import->action === Import::ACTION_DELETE) {
            if (! $this->localeExists($code)) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, 'code');

                return false;
            }

            return true;
        }

        /**
         * Duplicate-code check within this import run.
         */
        if (in_array($code, $this->codesSeenInBatch, strict: true)) {
            $this->skipRow($rowNumber, self::ERROR_DUPLICATE_CODE, 'code');

            return false;
        }

        $validator = Validator::make($rowData, [
            'code'   => ['required', 'string', new Code],
            'status' => ['nullable', 'in:0,1'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $attribute => $messages) {
                $errorCode = array_key_first($validator->failed()[$attribute] ?? []);

                $this->skipRow($rowNumber, $errorCode ?? self::ERROR_CODE_SYSTEM_EXCEPTION, $attribute, current($messages));
            }
        }

        $isValid = ! $this->errorHelper->isRowInvalid($rowNumber);

        if ($isValid) {
            $this->codesSeenInBatch[] = $code;
        }

        return $isValid;
    }

    /**
     * Import a single validated batch.
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action === Import::ACTION_DELETE) {
            $this->deleteLocaleData($batch);
        } else {
            $this->saveLocaleData($batch);
        }

        $this->importBatchRepository->update([
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
     * Persist locales from the current batch (insert or update).
     */
    protected function saveLocaleData(JobTrackBatchContract $batch): void
    {
        $filters = $this->getFilters();
        $statusFilter = $filters['status'] ?? 'All';

        $codes = array_column($batch->data, 'code');

        $this->loadExistingLocales($codes);

        foreach ($batch->data as $rowData) {
            $code = $rowData['code'];
            $status = isset($rowData['status']) ? (int) $rowData['status'] : 1;

            /**
             * Respect the "Enable" filter: only process enabled locales from the file.
             */
            if ($statusFilter === 'enable' && $status !== 1) {
                continue;
            }

            if (isset($this->existingLocales[$code])) {
                $this->localeRepository->update(
                    ['status' => $status],
                    $this->existingLocales[$code],
                );

                $this->updatedItemsCount++;
            } else {
                $this->localeRepository->create([
                    'code'   => $code,
                    'status' => $status,
                ]);

                $this->createdItemsCount++;
            }
        }
    }

    /**
     * Delete locales that appear in the current batch.
     */
    protected function deleteLocaleData(JobTrackBatchContract $batch): void
    {
        $filters = $this->getFilters();
        $statusFilter = $filters['status'] ?? 'All';

        $codes = array_column($batch->data, 'code');

        $this->loadExistingLocales($codes);

        foreach ($batch->data as $rowData) {
            $code = $rowData['code'];
            $status = isset($rowData['status']) ? (int) $rowData['status'] : 1;

            /**
             * Respect the "Enable" filter: only process enabled locales from the file.
             */
            if ($statusFilter === 'Enable' && $status !== 1) {
                continue;
            }

            if (! isset($this->existingLocales[$code])) {
                continue;
            }

            $this->localeRepository->delete($this->existingLocales[$code]);

            $this->deletedItemsCount++;
        }
    }

    /**
     * Retrieve filters from the job instance.
     */
    protected function getFilters(): array
    {
        return $this->import->jobInstance->filters ?? [];
    }

    /**
     * Load the existing locale codes → ids into the in-memory map
     * to avoid one DB query per row.
     *
     * @param  string[]  $codes
     */
    protected function loadExistingLocales(array $codes): void
    {
        $missing = array_diff($codes, array_keys($this->existingLocales));

        if (empty($missing)) {
            return;
        }

        DB::table('locales')
            ->whereIn('code', $missing)
            ->select('code', 'id')
            ->get()
            ->each(function ($row) {
                $this->existingLocales[$row->code] = $row->id;
            });
    }

    /**
     * Check whether a locale code already exists in the database.
     */
    protected function localeExists(?string $code): bool
    {
        if (! $code) {
            return false;
        }

        if (isset($this->existingLocales[$code])) {
            return true;
        }

        $id = DB::table('locales')->where('code', $code)->value('id');

        if ($id) {
            $this->existingLocales[$code] = $id;

            return true;
        }

        return false;
    }
}
