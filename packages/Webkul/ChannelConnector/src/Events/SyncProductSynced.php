<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class SyncProductSynced
{
    public function __construct(
        public readonly Product $product,
        public readonly SyncResult $syncResult
    ) {}
}
