<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelSyncJob;

class SyncFailed
{
    public function __construct(public readonly ChannelSyncJob $syncJob) {}
}
