<?php

namespace Webkul\ChannelConnector\Events;

class ConnectorCreating
{
    public function __construct(public readonly array $data) {}
}
