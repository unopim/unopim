<?php

namespace Webkul\DataTransfer\Helpers\Importers\User;

use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Illuminate\Support\Facades\Validator;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Concerns\GuardsLastFullAccess;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Contracts\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Services\RoleAccessGuard;

class Importer extends AbstractImporter
{
    use GuardsLastFullAccess;

    /**
     * Filter value to import active rows only.
     */
    public const FILTER_STATUS_ACTIVE = 'active';

    /**
     * Filter value to import all rows.
     */
    public const FILTER_STATUS_ALL = 'all';

    /**
     * Error code for non existing email
     */
    public const ERROR_EMAIL_NOT_FOUND_FOR_DELETE = 'email_not_found_to_delete';

    /**
     * Error code for invalid role
     */
    public const ERROR_INVALID_ROLE = 'invalid_role';

    /**
     * Error code for invalid locale
     */
    public const ERROR_INVALID_LOCALE = 'invalid_locale';

    /**
     * Error code for a role the importing admin cannot assign or grant
     */
    public const ERROR_CANNOT_ASSIGN_ROLE = 'cannot_assign_role';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'name',
        'email',
        'image',
        'status',
        'role_name',
        'permission_type',
        'permissions',
        'timezone',
        'ui_locale_code',
    ];

    /**
     * Permanent entity columns
     */
    protected array $permanentAttributes = ['email', 'name', 'role_name'];

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_EMAIL_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.users.validation.errors.email-not-found-to-delete',
        self::ERROR_INVALID_ROLE               => 'data_transfer::app.importers.users.validation.errors.invalid-role',
        self::ERROR_INVALID_LOCALE             => 'data_transfer::app.importers.users.validation.errors.invalid-locale',
        self::ERROR_CANNOT_ASSIGN_ROLE         => 'admin::app.settings.users.cannot-escalate-role',
        self::ERROR_LAST_FULL_ACCESS           => 'admin::app.settings.roles.last-full-access',
    ];

    /**
     * Roles loaded during validation, keyed by id
     *
     * @var array<int, Role|null>
     */
    protected array $roles = [];

    /**
     * Create a new helper instance.
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected Storage $userStorage,
        protected FileStorer $fileStorer,
        protected RoleAccessGuard $roleAccessGuard
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

    public function validateData(): void
    {
        $this->userStorage->init();

        parent::validateData();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            $this->userStorage->loadAdmins([$rowData['email']]);

            $adminId = $this->userStorage->getAdminId($rowData['email']);

            if (! $adminId) {
                $this->skipRow($rowNumber, self::ERROR_EMAIL_NOT_FOUND_FOR_DELETE, $rowData['email']);

                return false;
            }

            if (! $this->holdsImportPermission('settings.users.users.delete', $rowNumber, self::ERROR_CANNOT_ASSIGN_ROLE, 'email')
                || ! $this->canManageExistingAdmin($rowData['email'], $rowNumber)) {
                return false;
            }

            return $this->keepsFullAccess($rowNumber, 'email', adminId: $this->deletedFullAccessAdminId($rowData));
        }

        if (! $this->shouldImportRow($rowData)) {
            return false;
        }

        $validator = Validator::make($rowData, [
            'name'            => ['required', 'string'],
            'email'           => ['required', 'email'],
            'role_name'       => ['required', 'string'],
            'permission_type' => ['nullable', 'in:all,custom'],
            'permissions'     => ['nullable', 'string'],
            'status'          => ['nullable', 'in:active,inactive'],
            'timezone'        => ['nullable', 'string', 'timezone:all_with_bc'],
            'ui_locale_code'  => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $this->skipRow($rowNumber, 'invalid_field', $attributeCode, current($message));
            }
        }

        if (! empty($rowData['role_name']) && ! $this->userStorage->getRoleId($rowData['role_name'])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_ROLE, $rowData['role_name']);
        }

        if (! empty($rowData['ui_locale_code']) && ! $this->userStorage->getLocaleId($rowData['ui_locale_code'])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_LOCALE, $rowData['ui_locale_code']);
        }

        if ($this->errorHelper->isRowInvalid($rowNumber)) {
            return false;
        }

        return $this->validateAccess($rowData, $rowNumber);
    }

    /**
     * Reject a row whose role the importing admin cannot assign or grant, or that leaves no active admin with full access.
     */
    protected function validateAccess(array $rowData, int $rowNumber): bool
    {
        $this->userStorage->loadAdmins([$rowData['email']]);

        if ($denial = $this->accessDenial($rowData)) {
            $this->skipRow($rowNumber, self::ERROR_CANNOT_ASSIGN_ROLE, $denial['columnName'], trans($denial['error']));

            return false;
        }

        return $this->keepsFullAccess($rowNumber, 'role_name', ...$this->fullAccessLoss($rowData));
    }

    /**
     * Column and translation key of the first access rule the row breaks for the importing admins against the users and roles as stored now, or null when it breaks none.
     *
     * @return array{columnName: string, error: string}|null
     */
    protected function accessDenial(array $rowData): ?array
    {
        $userPermission = $this->userStorage->getAdminId($rowData['email']) ? 'settings.users.users.edit' : 'settings.users.users.create';

        if ($error = $this->importPermissionError($userPermission)) {
            return ['columnName' => 'email', 'error' => $error];
        }

        if ($this->existingAdminAccessError($rowData['email']) !== null) {
            return ['columnName' => 'email', 'error' => 'admin::app.common.unauthorized'];
        }

        $role = $this->findRole($this->userStorage->getRoleId($rowData['role_name']));
        $permissionType = $rowData['permission_type'] ?? null;

        if (! empty($permissionType)
            && $error = $this->importPermissionError($role instanceof Role ? 'settings.roles.edit' : 'settings.roles.create')) {
            return ['columnName' => 'role_name', 'error' => $error];
        }

        if (! empty($permissionType)
            && $error = $this->importAccessError(fn ($admin): ?string => $this->roleAccessGuard->currentAccessError($admin, $role))) {
            return ['columnName' => 'role_name', 'error' => $error];
        }

        $error = $this->importAccessError(fn ($admin): ?string => empty($permissionType)
            ? $this->roleAccessGuard->assignmentError($admin, $role)
            : $this->roleAccessGuard->grantError(
                $admin,
                $permissionType,
                empty($rowData['permissions']) ? [] : explode(',', $rowData['permissions'])
            ));

        return $error ? ['columnName' => 'role_name', 'error' => $error] : null;
    }

    /**
     * Reject the row when the importing admins could not assign the current role of the existing admin it changes or deletes.
     */
    protected function canManageExistingAdmin(string $email, int $rowNumber): bool
    {
        if ($this->existingAdminAccessError($email) === null) {
            return true;
        }

        $this->skipRow($rowNumber, self::ERROR_CANNOT_ASSIGN_ROLE, 'email', trans('admin::app.common.unauthorized'));

        return false;
    }

    /**
     * Translation key explaining why the importing admins cannot manage the existing admin with this email, or null when they can or no such admin exists.
     */
    protected function existingAdminAccessError(string $email): ?string
    {
        if (! $this->userStorage->getAdminId($email)) {
            return null;
        }

        $currentRole = $this->findRole($this->userStorage->getAdminRoleId($email));

        return $this->importAccessError(fn ($admin): ?string => $this->roleAccessGuard->assignmentError($admin, $currentRole));
    }

    /**
     * The role the row takes full access away from and the active full-access admin it moves, disables or demotes.
     *
     * @return array{roleId: ?int, adminId: ?int}
     */
    protected function fullAccessLoss(array $rowData): array
    {
        $roleId = $this->userStorage->getRoleId($rowData['role_name']) ?: null;
        $permissionType = $rowData['permission_type'] ?? null;

        $demotesRole = ! empty($permissionType) && $permissionType !== RoleAccessGuard::FULL_ACCESS;

        $fullAccessAdmin = $this->activeFullAccessAdmin($rowData['email']);

        $leavesFullAccess = $fullAccessAdmin !== null
            && ($fullAccessAdmin['role_id'] !== $roleId
                || ($rowData['status'] ?? self::FILTER_STATUS_ACTIVE) !== self::FILTER_STATUS_ACTIVE);

        return [
            'roleId'  => $demotesRole ? $roleId : null,
            'adminId' => $leavesFullAccess ? $fullAccessAdmin['id'] : null,
        ];
    }

    /**
     * Id of the active full-access admin a delete row removes, or null.
     */
    protected function deletedFullAccessAdminId(array $rowData): ?int
    {
        return $this->activeFullAccessAdmin($rowData['email'])['id'] ?? null;
    }

    /**
     * Role by id, loaded once per import.
     */
    protected function findRole(?int $roleId): ?Role
    {
        if (! $roleId) {
            return null;
        }

        return $this->roles[$roleId] ??= $this->roleRepository->find($roleId);
    }

    protected function shouldImportRow(array $rowData): bool
    {
        if ($this->getStatusFilter() !== self::FILTER_STATUS_ACTIVE) {
            return true;
        }

        return ($rowData['status'] ?? self::FILTER_STATUS_ACTIVE) === self::FILTER_STATUS_ACTIVE;
    }

    protected function getStatusFilter(): string
    {
        $filters = $this->import->jobInstance->filters ?? [];

        return $filters['status'] ?? self::FILTER_STATUS_ALL;
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        $this->writeWithFullAccessLock(function () use ($batch): void {
            if ($batch->jobTrack->action == Import::ACTION_DELETE) {
                $this->deleteUserData($batch);
            } else {
                $this->saveUserData($batch);
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

    protected function deleteUserData(JobTrackBatchContract $batch): bool
    {
        $this->roles = [];

        $this->userStorage->loadAdmins(Arr::pluck($batch->data, 'email'));

        $idsToDelete = [];

        $permissionError = $this->importPermissionError('settings.users.users.delete');

        foreach ($batch->data as $rowData) {
            $id = $this->userStorage->getAdminId($rowData['email']);

            if (! $id) {
                continue;
            }

            $reason = match (true) {
                $permissionError !== null                                                           => trans($permissionError),
                $this->existingAdminAccessError($rowData['email']) !== null                         => trans('admin::app.common.unauthorized'),
                ! $this->allowsFullAccessChange(adminId: $this->deletedFullAccessAdminId($rowData)) => trans($this->messages[self::ERROR_LAST_FULL_ACCESS]),
                default                                                                             => null,
            };

            if ($reason !== null) {
                $this->logSkippedRow($rowData['email'], $reason);

                continue;
            }

            $idsToDelete[] = $id;
        }

        $idsToDelete = array_unique($idsToDelete);

        foreach ($idsToDelete as $id) {
            $this->adminRepository->delete($id);
            $this->deletedItemsCount++;
        }

        return true;
    }

    protected function saveUserData(JobTrackBatchContract $batch): bool
    {
        $this->roles = [];

        $this->userStorage->init();

        $this->userStorage->loadAdmins(Arr::pluck($batch->data, 'email'));

        foreach ($batch->data as $rowData) {
            if ($denial = $this->accessDenial($rowData)) {
                $this->logSkippedRow($rowData['email'], trans($denial['error']));

                continue;
            }

            if (! $this->allowsFullAccessChange(...$this->fullAccessLoss($rowData))) {
                $this->logSkippedRow($rowData['email'], trans($this->messages[self::ERROR_LAST_FULL_ACCESS]));

                continue;
            }

            $id = $this->userStorage->getAdminId($rowData['email']);

            $roleId = $this->userStorage->getRoleId($rowData['role_name']);

            if (! empty($rowData['permission_type'])) {
                $roleData = [
                    'name'            => $rowData['role_name'],
                    'permission_type' => $rowData['permission_type'],
                    'permissions'     => empty($rowData['permissions']) ? null : explode(',', $rowData['permissions']),
                ];

                if ($roleId) {
                    $this->roleRepository->update($roleData, $roleId);
                } else {
                    $role = $this->roleRepository->create($roleData);
                    $roleId = $role->id;

                    $this->userStorage->loadRoles();
                }
            }

            $data = [
                'name'         => EscapeFormulaOperators::unescapeValue($rowData['name']),
                'email'        => $rowData['email'],
                'status'       => ($rowData['status'] ?? 'active') == 'active' ? 1 : 0,
                'role_id'      => $roleId,
                'timezone'     => core()->resolveTimezone($rowData['timezone'] ?? null),
                'ui_locale_id' => $this->userStorage->getLocaleId($rowData['ui_locale_code'] ?? ''),
            ];

            if ($id) {
                $admin = $this->adminRepository->find($id);

                if (blank($rowData['timezone'] ?? null)) {
                    unset($data['timezone']);
                }

                if ($imagePath = $this->storeUserImage($rowData['image'] ?? null, $admin)) {
                    $data['image'] = $imagePath;
                }

                $this->adminRepository->update($data, $id);
                $this->userStorage->setAdmin($rowData['email'], $id, $roleId);
                $this->updatedItemsCount++;
            } else {
                $data['password'] = null;

                $admin = $this->adminRepository->create($data);

                if ($imagePath = $this->storeUserImage($rowData['image'] ?? null, $admin)) {
                    $this->adminRepository->update(['image' => $imagePath], $admin->id);
                }

                $this->userStorage->setAdmin($admin->email, $admin->id, $roleId);
                $this->createdItemsCount++;
            }
        }

        return true;
    }

    protected function storeUserImage(?string $image, mixed $admin): ?string
    {
        $image = $this->resolveImportImagePath($image);

        if (! $image) {
            return null;
        }

        $file = new File(StorageFacade::disk('public')->path($image));

        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return null;
        }

        $newPath = $this->fileStorer->storeAs(
            path: 'admins'.DIRECTORY_SEPARATOR.$admin->id,
            name: basename($image),
            file: $file,
            options: ['disk' => 'public']
        );

        if (
            ! empty($admin->image)
            && $admin->image !== $newPath
            && StorageFacade::disk('public')->exists($admin->image)
        ) {
            StorageFacade::disk('public')->delete($admin->image);
        }

        return $newPath;
    }

    protected function resolveImportImagePath(?string $image): ?string
    {
        $image = $image ? ltrim(trim($image), '/') : null;

        if (! $image) {
            return null;
        }

        $basePath = trim((string) ($this->import->images_directory_path ?? ''), '/');

        if ($basePath === '0') {
            $basePath = '';
        }

        $path = $basePath !== '' ? $basePath.'/'.$image : $image;

        return $this->isInsideImagesDirectory($path, $basePath) ? $path : null;
    }

    /**
     * Whether the path names an existing file inside the import's images directory on the public disk, after resolving every symlink.
     */
    protected function isInsideImagesDirectory(string $path, string $basePath): bool
    {
        if (str_contains($path, "\0") || in_array('..', preg_split('#[/\\\\]+#', $path), true)) {
            return false;
        }

        $disk = StorageFacade::disk('public');

        $root = realpath($disk->path($basePath));
        $file = realpath($disk->path($path));

        return $root !== false
            && $file !== false
            && is_file($file)
            && str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    }
}
