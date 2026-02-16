<?php

namespace Webkul\ChannelConnector\Repositories;

use Webkul\Core\Eloquent\Repository;

class ChannelSyncConflictRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\ChannelConnector\Contracts\ChannelSyncConflict::class;
    }
}
