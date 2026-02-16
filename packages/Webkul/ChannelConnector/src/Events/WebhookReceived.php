<?php

namespace Webkul\ChannelConnector\Events;

use Webkul\ChannelConnector\Models\ChannelConnector;

class WebhookReceived
{
    public function __construct(
        public readonly ChannelConnector $connector,
        public readonly array $payload
    ) {}
}
