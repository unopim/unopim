<?php

namespace Webkul\ChannelConnector\Repositories;

use Webkul\Core\Eloquent\Repository;

class ChannelSyncJobRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\ChannelConnector\Contracts\ChannelSyncJob::class;
    }
}
