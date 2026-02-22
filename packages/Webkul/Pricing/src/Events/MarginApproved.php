<?php

namespace Webkul\Pricing\Events;

use Webkul\Pricing\Models\MarginProtectionEvent;

class MarginApproved
{
    public function __construct(
        public readonly MarginProtectionEvent $marginEvent,
        public readonly int $approverId
    ) {}
}
