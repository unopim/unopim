<?php

namespace Webkul\DataTransfer\Helpers\Importers\Role;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Repositories\RoleRepository;

class Importer extends AbstractImporter
{
    /**
     * Error code for duplicated name
     */
    public const ERROR_DUPLICATE_NAME = 'duplicate_name';

    /**
     * Error code for non existing name
     */
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'name_not_found_to_delete';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'id',
        'name',
        'description',
        'permission_type',
        'permissions',
    ];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['name', 'permission_type'];

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_DUPLICATE_NAME            => 'data_transfer::app.importers.roles.validation.errors.duplicate-name',
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.roles.validation.errors.name-not-found-to-delete',
    ];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected RoleRepository $roleRepository,
        protected Storage $roleStorage
    ) {
        parent::__construct($importBatchRepository);
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
     * Validate data.
     */
    public function validateData(): void
    {
        $this->roleStorage->init();

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
            if (! $this->roleStorage->get($rowData['name'])) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['name']);

                return false;
            }

            return true;
        }

        $validator = Validator::make($rowData, [
            'name'            => 'required|string',
            'permission_type' => 'required|in:all,custom',
            'description'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $this->skipRow($rowNumber, 'invalid_field', $attributeCode, current($message));
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    /**
     * Start the import process
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteRoleData($batch);
        } else {
            $this->saveRoleData($batch);
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
     * Delete roles from current batch
     */
    protected function deleteRoleData(JobTrackBatchContract $batch): bool
    {
        $this->roleStorage->load(Arr::pluck($batch->data, 'name'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            $id = $this->roleStorage->get($rowData['name']);

            if ($id) {
                $idsToDelete[] = $id;
            }
        }

        $idsToDelete = array_unique($idsToDelete);

        if (! empty($idsToDelete)) {
            foreach ($idsToDelete as $id) {
                $this->roleRepository->delete($id);
                $this->deletedItemsCount++;
            }
        }

        return true;
    }

    /**
     * Save roles from current batch
     */
    protected function saveRoleData(JobTrackBatchContract $batch): bool
    {
        $this->roleStorage->load(Arr::pluck($batch->data, 'name'));

        foreach ($batch->data as $rowData) {
            $id = $this->roleStorage->get($rowData['name']);

            $data = [
                'name'            => EscapeFormulaOperators::unescapeValue($rowData['name']),
                'description'     => EscapeFormulaOperators::unescapeValue($rowData['description'] ?? ''),
                'permission_type' => $rowData['permission_type'],
                'permissions'     => ! empty($rowData['permissions']) ? explode(',', $rowData['permissions']) : [],
            ];

            if ($id) {
                $this->roleRepository->update($data, $id);
                $this->updatedItemsCount++;
            } else {
                $role = $this->roleRepository->create($data);
                $this->roleStorage->set($role->name, $role->id);
                $this->createdItemsCount++;
            }
        }

        return true;
    }
}
