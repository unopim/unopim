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
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

class Importer extends AbstractImporter
{
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
    ];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected Storage $userStorage,
        protected FileStorer $fileStorer
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
        $this->userStorage->init();

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
            $this->userStorage->loadAdmins([$rowData['email']]);

            if (! $this->userStorage->getAdminId($rowData['email'])) {
                $this->skipRow($rowNumber, self::ERROR_EMAIL_NOT_FOUND_FOR_DELETE, $rowData['email']);

                return false;
            }

            return true;
        }

        if (! $this->shouldImportRow($rowData)) {
            return false;
        }

        $validator = Validator::make($rowData, [
            'name'            => 'required|string',
            'email'           => 'required|email',
            'role_name'       => 'required|string',
            'permission_type' => 'nullable|in:all,custom',
            'permissions'     => 'nullable|string',
            'status'          => 'nullable|in:active,inactive',
            'timezone'        => 'nullable|string',
            'ui_locale_code'  => 'nullable|string',
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

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    /**
     * Determine whether the current row should be imported for this profile.
     */
    protected function shouldImportRow(array $rowData): bool
    {
        return ! (
            $this->getStatusFilter() === self::FILTER_STATUS_ACTIVE
            && ($rowData['status'] ?? self::FILTER_STATUS_ACTIVE) !== self::FILTER_STATUS_ACTIVE
        );
    }

    /**
     * Get the selected status filter for this import profile.
     */
    protected function getStatusFilter(): string
    {
        $filters = $this->import->jobInstance->filters ?? [];

        return $filters['status'] ?? self::FILTER_STATUS_ALL;
    }

    /**
     * Start the import process
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteUserData($batch);
        } else {
            $this->saveUserData($batch);
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
     * Delete users from current batch
     */
    protected function deleteUserData(JobTrackBatchContract $batch): bool
    {
        $this->userStorage->loadAdmins(Arr::pluck($batch->data, 'email'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            $id = $this->userStorage->getAdminId($rowData['email']);

            if ($id) {
                $idsToDelete[] = $id;
            }
        }

        $idsToDelete = array_unique($idsToDelete);

        if (! empty($idsToDelete)) {
            foreach ($idsToDelete as $id) {
                $this->adminRepository->delete($id);
                $this->deletedItemsCount++;
            }
        }

        return true;
    }

    /**
     * Save users from current batch
     */
    protected function saveUserData(JobTrackBatchContract $batch): bool
    {
        $this->userStorage->init();

        $this->userStorage->loadAdmins(Arr::pluck($batch->data, 'email'));

        foreach ($batch->data as $rowData) {
            $id = $this->userStorage->getAdminId($rowData['email']);

            /**
             * Create or Update Role if permissions are provided
             */
            $roleId = $this->userStorage->getRoleId($rowData['role_name']);

            if (! empty($rowData['permission_type'])) {
                $roleData = [
                    'name'            => $rowData['role_name'],
                    'permission_type' => $rowData['permission_type'],
                    'permissions'     => ! empty($rowData['permissions']) ? explode(',', $rowData['permissions']) : null,
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
                'timezone'     => $rowData['timezone'] ?? config('app.timezone'),
                'ui_locale_id' => $this->userStorage->getLocaleId($rowData['ui_locale_code'] ?? ''),
            ];

            if ($id) {
                $admin = $this->adminRepository->find($id);

                if ($imagePath = $this->storeUserImage($rowData['image'] ?? null, $admin)) {
                    $data['image'] = $imagePath;
                }

                $this->adminRepository->update($data, $id);
                $this->updatedItemsCount++;
            } else {
                $data['password'] = null;

                $admin = $this->adminRepository->create($data);

                if ($imagePath = $this->storeUserImage($rowData['image'] ?? null, $admin)) {
                    $this->adminRepository->update(['image' => $imagePath], $admin->id);
                }

                $this->userStorage->setAdmin($admin->email, $admin->id);
                $this->createdItemsCount++;
            }
        }

        return true;
    }

    /**
     * Store the imported user image in the normal admin image directory.
     */
    protected function storeUserImage(?string $image, mixed $admin): ?string
    {
        $image = $this->resolveImportImagePath($image);

        if (! $image || ! StorageFacade::disk('public')->exists($image)) {
            return null;
        }

        $newPath = $this->fileStorer->storeAs(
            path: 'admins'.DIRECTORY_SEPARATOR.$admin->id,
            name: basename($image),
            file: new File(StorageFacade::disk('public')->path($image)),
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

    /**
     * Build the public-disk path for an imported image reference.
     */
    protected function resolveImportImagePath(?string $image): ?string
    {
        $image = $image ? ltrim(trim($image), '/') : null;

        if (! $image) {
            return null;
        }

        $basePath = trim((string) ($this->import->images_directory_path ?? ''), '/');

        return $basePath ? $basePath.'/'.$image : $image;
    }
}
