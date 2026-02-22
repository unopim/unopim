<?php

namespace Webkul\Pricing\Events;

use Webkul\Pricing\Models\MarginProtectionEvent;

class MarginRejected
{
    public function __construct(
        public readonly MarginProtectionEvent $marginEvent,
        public readonly int $rejectedById,
        public readonly string $reason
    ) {}
}
