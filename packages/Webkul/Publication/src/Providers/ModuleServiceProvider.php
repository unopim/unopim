<?php

namespace Webkul\Publication\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationCarrierIssuance;
use Webkul\Publication\Models\PublicationGtin;
use Webkul\Publication\Models\PublicationPublishAttempt;
use Webkul\Publication\Models\PublicationRelease;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Models\PublicationVersionDocument;
use Webkul\Publication\Models\PublicationVersionPayload;
use Webkul\Publication\Models\PublicationViewStat;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Publication::class,
        PublicationCarrierIssuance::class,
        PublicationGtin::class,
        PublicationRelease::class,
        PublicationVersion::class,
        PublicationVersionPayload::class,
        PublicationVersionDocument::class,
        PublicationViewStat::class,
        PublicationPublishAttempt::class,
    ];
}
