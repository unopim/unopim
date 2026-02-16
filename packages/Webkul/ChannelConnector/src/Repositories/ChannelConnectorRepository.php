<?php

namespace Webkul\ChannelConnector\Repositories;

use Webkul\Core\Eloquent\Repository;

class ChannelConnectorRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\ChannelConnector\Contracts\ChannelConnector::class;
    }
}
