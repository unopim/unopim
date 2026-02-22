<?php

namespace Webkul\Pricing\Events;

use Webkul\Pricing\Models\MarginProtectionEvent;

class MarginBlocked
{
    public function __construct(
        public readonly MarginProtectionEvent $marginEvent
    ) {}
}
