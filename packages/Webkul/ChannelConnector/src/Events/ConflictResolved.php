<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelSyncConflict;

class ConflictResolved
{
    public function __construct(public readonly ChannelSyncConflict $conflict) {}
}
