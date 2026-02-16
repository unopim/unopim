<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelConnector;

class ConnectorDeleting
{
    public function __construct(public readonly ChannelConnector $connector) {}
}
