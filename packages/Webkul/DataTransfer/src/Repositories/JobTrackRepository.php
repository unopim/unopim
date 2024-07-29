<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobTrack;

class JobTrackRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobTrack::class;
    }
}
