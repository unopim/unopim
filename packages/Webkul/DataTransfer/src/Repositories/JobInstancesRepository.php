<?php

namespace Webkul\DataTransfer\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobInstances;

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
        if (isset($data['allowed_errors']) && $data['allowed_errors'] === '') {
            $data['allowed_errors'] = 0;
        }

        /**
         * PostgreSQL rejects an empty string where MySQL and MariaDB coerce it,
         * so only the nullable paths are driver-specific. Testing for pgsql and
         * letting every other driver fall through keeps a newly added driver
         * working rather than silently unnormalised.
         */
        if (DB::getDriverName() === 'pgsql') {
            foreach (['file_path', 'images_directory_path'] as $column) {
                if (isset($data[$column]) && $data[$column] === '') {
                    $data[$column] = null;
                }
            }
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
