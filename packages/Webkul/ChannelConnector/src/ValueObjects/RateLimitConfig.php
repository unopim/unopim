<?php

namespace Webkul\ChannelConnector\ValueObjects;

class RateLimitConfig
{
    public function __construct(
        public readonly ?int $requestsPerSecond = null,
        public readonly ?int $requestsPerMinute = null,
        public readonly ?int $costPerQuery = null,
        public readonly ?int $costPerMutation = null,
        public readonly ?int $maxCostPerSecond = null,
    ) {}
}
