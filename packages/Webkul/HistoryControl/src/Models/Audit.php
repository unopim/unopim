<?php

namespace Webkul\HistoryControl\Models;

use OwenIt\Auditing\Models\Audit as BaseAudit;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Audit extends BaseAudit
{
    use BelongsToTenant;
}
