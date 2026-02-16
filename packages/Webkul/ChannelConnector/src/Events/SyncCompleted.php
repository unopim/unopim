<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelSyncJob;

class SyncCompleted
{
    public function __construct(public readonly ChannelSyncJob $syncJob) {}
}
