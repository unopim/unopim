<?php

namespace Webkul\DataTransfer\Helpers\Exporters\User;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Importers\User\Storage as UserStorage;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Repositories\RoleRepository;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected RoleRepository $roleRepository,
        protected LocaleRepository $localeRepository,
        protected UserStorage $userStorage
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initializeFileBuffer();

        $this->userStorage->init();
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $users = $this->prepareUsers($batch, $filePath);

        $this->exportBuffer->write($users);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Prepare users from current batch
     */
    public function prepareUsers(JobTrackBatchContract $batch, mixed $filePath = null)
    {
        $users = [];

        $roles = $this->roleRepository->all();
        $locales = $this->localeRepository->all();
        $withMedia = (bool) ($this->getFilters()['with_media'] ?? false);

        foreach ($batch->data as $rowData) {
            $role = $roles->firstWhere('id', $rowData['role_id']);
            $locale = $locales->firstWhere('id', $rowData['ui_locale_id']);
            $imagePath = $this->resolveUserImagePath($rowData['image'] ?? '');
            $exportedImage = $imagePath ? Str::replace('\\', '/', $imagePath) : '';

            if (
                $withMedia
                && ! empty($imagePath)
                && $filePath
                && method_exists($filePath, 'getTemporaryPath')
            ) {
                $this->copyMedia($imagePath, $filePath->getTemporaryPath().'/'.$exportedImage);
            }

            $users[] = [
                'id'              => $rowData['id'],
                'name'            => EscapeFormulaOperators::escapeValue($rowData['name']),
                'email'           => $rowData['email'],
                'image'           => $exportedImage,
                'status'          => $rowData['status'] ? 'active' : 'inactive',
                'role_name'       => $role?->name ?? '',
                'permission_type' => $role?->permission_type ?? '',
                'permissions'     => ! empty($role->permissions) ? implode(',', $role->permissions) : '',
                'timezone'        => $rowData['timezone'],
                'ui_locale_code'  => $locale?->code ?? '',
            ];

            $this->createdItemsCount++;
        }

        return $users;
    }

    /**
     * Normalize stored user image paths to a public-disk relative path.
     */
    protected function resolveUserImagePath(?string $imagePath): string
    {
        $imagePath = trim((string) $imagePath);

        if ($imagePath === '') {
            return '';
        }

        $candidates = array_filter(array_unique([
            str_replace('\\', '/', $imagePath),
            ltrim(str_replace('\\', '/', $imagePath), '/'),
            preg_replace('#^storage/app/public/#', '', str_replace('\\', '/', $imagePath)),
            preg_replace('#^storage/public/#', '', str_replace('\\', '/', $imagePath)),
            preg_replace('#^storage/#', '', ltrim(str_replace('\\', '/', $imagePath), '/')),
        ]));

        foreach ($candidates as $candidate) {
            if (Storage::exists($candidate)) {
                return $candidate;
            }
        }

        return ltrim(str_replace('\\', '/', $imagePath), '/');
    }

    /**
     * Get results
     *
     * @return \Iterator
     */
    protected function getResults()
    {
        $filters = $this->getFilters();

        $query = $this->getSource()->query();

        if (
            ! empty($filters['status'])
            && $filters['status'] === 'active'
        ) {
            $query->where('status', 1);
        }

        return $query->get()->getIterator();
    }
}
