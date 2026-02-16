<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelConnector;

class ConnectorCreated
{
    public function __construct(public readonly ChannelConnector $connector) {}
}
