<?php

namespace Webkul\ChannelConnector\Repositories;

use Webkul\Core\Eloquent\Repository;

class ChannelFieldMappingRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\ChannelConnector\Contracts\ChannelFieldMapping::class;
    }
}
