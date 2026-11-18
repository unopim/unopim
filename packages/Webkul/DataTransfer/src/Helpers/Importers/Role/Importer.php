<?php

namespace Webkul\DataTransfer\Helpers\Importers\Role;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Concerns\GuardsLastFullAccess;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Services\RoleAccessGuard;

class Importer extends AbstractImporter
{
    use GuardsLastFullAccess;

    /**
     * Error code for duplicated name
     */
    public const ERROR_DUPLICATE_NAME = 'duplicate_name';

    /**
     * Error code for non existing name
     */
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'name_not_found_to_delete';

    /**
     * Error code for a role the importing admin cannot grant
     */
    public const ERROR_CANNOT_GRANT_ACCESS = 'cannot_grant_access';

    /**
     * Error code for deleting a role that admins still use
     */
    public const ERROR_ROLE_IN_USE = 'role_in_use';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
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
        self::ERROR_CANNOT_GRANT_ACCESS       => 'admin::app.settings.roles.cannot-grant-unheld-permissions',
        self::ERROR_LAST_FULL_ACCESS          => 'admin::app.settings.roles.last-full-access',
        self::ERROR_ROLE_IN_USE               => 'admin::app.settings.roles.being-used',
    ];

    /**
     * Create a new helper instance.
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected RoleRepository $roleRepository,
        protected Storage $roleStorage,
        protected AdminRepository $adminRepository,
        protected RoleAccessGuard $roleAccessGuard
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
        $this->roleStorage->init();

        parent::validateData();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            $roleId = $this->roleStorage->get($rowData['name']);

            if (! $roleId) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['name']);

                return false;
            }

            if (! $this->holdsImportPermission('settings.roles.delete', $rowNumber, self::ERROR_CANNOT_GRANT_ACCESS, 'name')) {
                return false;
            }

            if ($this->roleRepository->find($roleId)?->admins()->exists()) {
                $this->skipRow($rowNumber, self::ERROR_ROLE_IN_USE, $rowData['name']);

                return false;
            }

            return true;
        }

        $validator = Validator::make($rowData, [
            'name'            => ['required', 'string'],
            'permission_type' => ['required', 'in:all,custom'],
            'description'     => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $this->skipRow($rowNumber, 'invalid_field', $attributeCode, current($message));
            }

            return false;
        }

        $this->validateAccess($rowData, $rowNumber);

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    /**
     * Reject a row that grants access the importing admin lacks, or that demotes the last active full-access role.
     */
    protected function validateAccess(array $rowData, int $rowNumber): void
    {
        if ($denial = $this->accessDenial($rowData)) {
            $this->skipRow($rowNumber, self::ERROR_CANNOT_GRANT_ACCESS, ...$denial);

            return;
        }

        $this->keepsFullAccess($rowNumber, 'permission_type', roleId: $this->demotedRoleId($rowData));
    }

    /**
     * Column and message of the first access rule the row breaks for the importing admins against the roles as stored now, or null when it breaks none.
     *
     * @return array{columnName: string, errorMessage: ?string}|null
     */
    protected function accessDenial(array $rowData): ?array
    {
        $permission = $this->roleStorage->get($rowData['name']) ? 'settings.roles.edit' : 'settings.roles.create';

        if ($error = $this->importPermissionError($permission)) {
            return ['columnName' => 'name', 'errorMessage' => trans($error)];
        }

        $permissions = empty($rowData['permissions']) ? [] : explode(',', $rowData['permissions']);

        if ($this->importAccessError(fn ($admin): ?string => $this->roleAccessGuard->grantError($admin, $rowData['permission_type'], $permissions))) {
            return ['columnName' => 'permission_type', 'errorMessage' => null];
        }

        return null;
    }

    /**
     * Id of the existing role the row leaves without full access, or null.
     */
    protected function demotedRoleId(array $rowData): ?int
    {
        if ($rowData['permission_type'] === RoleAccessGuard::FULL_ACCESS) {
            return null;
        }

        return $this->roleStorage->get($rowData['name']) ?: null;
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        $this->writeWithFullAccessLock(function () use ($batch): void {
            if ($batch->jobTrack->action == Import::ACTION_DELETE) {
                $this->deleteRoleData($batch);
            } else {
                $this->saveRoleData($batch);
            }
        });

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

    protected function deleteRoleData(JobTrackBatchContract $batch): bool
    {
        $this->roleStorage->load(Arr::pluck($batch->data, 'name'));

        $namesToDelete = [];

        foreach ($batch->data as $rowData) {
            $id = $this->roleStorage->get($rowData['name']);

            if ($id) {
                $namesToDelete[$id] = $rowData['name'];
            }
        }

        $permissionError = $this->importPermissionError('settings.roles.delete');

        foreach ($namesToDelete as $id => $name) {
            $reason = match (true) {
                $permissionError !== null                                    => trans($permissionError),
                ! $this->allowsFullAccessChange(roleId: $id)                 => trans($this->messages[self::ERROR_LAST_FULL_ACCESS]),
                (bool) $this->roleRepository->find($id)?->admins()->exists() => trans($this->messages[self::ERROR_ROLE_IN_USE]),
                default                                                      => null,
            };

            if ($reason !== null) {
                $this->logSkippedRow($name, $reason);

                continue;
            }

            $this->roleRepository->delete($id);
            $this->deletedItemsCount++;
        }

        return true;
    }

    protected function saveRoleData(JobTrackBatchContract $batch): bool
    {
        $this->roleStorage->load(Arr::pluck($batch->data, 'name'));

        foreach ($batch->data as $rowData) {
            if ($denial = $this->accessDenial($rowData)) {
                $this->logSkippedRow($rowData['name'], $denial['errorMessage'] ?? trans($this->messages[self::ERROR_CANNOT_GRANT_ACCESS]));

                continue;
            }

            if (! $this->allowsFullAccessChange(roleId: $this->demotedRoleId($rowData))) {
                $this->logSkippedRow($rowData['name'], trans($this->messages[self::ERROR_LAST_FULL_ACCESS]));

                continue;
            }

            $id = $this->roleStorage->get($rowData['name']);

            $data = [
                'name'            => EscapeFormulaOperators::unescapeValue($rowData['name']),
                'description'     => EscapeFormulaOperators::unescapeValue($rowData['description'] ?? ''),
                'permission_type' => $rowData['permission_type'],
                'permissions'     => empty($rowData['permissions']) ? [] : explode(',', $rowData['permissions']),
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
