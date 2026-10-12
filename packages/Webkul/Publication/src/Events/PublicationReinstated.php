<?php

namespace Webkul\Publication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Publication\Models\Publication;

class PublicationReinstated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Publication $publication,
    ) {}
}
