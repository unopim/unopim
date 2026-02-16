<?php

namespace Webkul\ChannelConnector\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProductChannelMappingRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\ChannelConnector\Contracts\ProductChannelMapping::class;
    }
}
