<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobTrackBatch;

class JobTrackBatchRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobTrackBatch::class;
    }
}
