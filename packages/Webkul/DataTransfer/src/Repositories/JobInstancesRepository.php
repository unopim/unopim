<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobInstances;
use Illuminate\Support\Facades\DB;

class JobInstancesRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobInstances::class;
    }

    /**
     * Normalize job instance data before save (DB-driver aware).
     */
    protected function normalizeData(array $data): array
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':

                if (isset($data['allowed_errors']) && $data['allowed_errors'] === '') {
                    $data['allowed_errors'] = 0;
                }

                if (isset($data['file_path']) && $data['file_path'] === '') {
                    $data['file_path'] = null;
                }
                if (isset($data['images_directory_path']) && $data['images_directory_path'] === '') {
                    $data['images_directory_path'] = null;
                }
                break;

            case 'mysql':
                
                if (isset($data['allowed_errors']) && $data['allowed_errors'] === '') {
                    $data['allowed_errors'] = 0;
                }
                break;
        }

        return $data;
    }

    /**
     * Update job instance.
     */
    public function update(array $data, $id)
    {
        $data = $this->normalizeData($data);
        return parent::update($data, $id);
    }

    /**
     * Create job instance.
     */
    public function create(array $data)
    {
        $data = $this->normalizeData($data);
        return parent::create($data);
    }
}
