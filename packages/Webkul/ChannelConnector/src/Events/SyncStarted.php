<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelSyncJob;

class SyncStarted
{
    public function __construct(public readonly ChannelSyncJob $syncJob) {}
}
