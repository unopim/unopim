<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelConnector;

class ConnectorUpdated
{
    public function __construct(public readonly ChannelConnector $connector) {}
}
