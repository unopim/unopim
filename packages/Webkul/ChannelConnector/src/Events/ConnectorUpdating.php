<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelConnector;

class ConnectorUpdating
{
    public function __construct(public readonly ChannelConnector $connector) {}
}
