<?php

namespace Webkul\Publication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Publication\Models\Publication;

/**
 * A publish was refused under the row lock after its payload had already been built, because the
 * publication left a publishable status in between (for example a concurrent redaction). A payload
 * builder whose build has side effects, such as copied assets, can listen for it to undo them.
 */
class PublicationPublishRefused
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Publication $publication,
    ) {}
}
