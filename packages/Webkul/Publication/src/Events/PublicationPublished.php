<?php

namespace Webkul\Publication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;

class PublicationPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Publication $publication,
        public readonly PublicationVersion $version,
    ) {}
}
