<?php

namespace Webkul\HistoryControl\Contracts;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

interface HistoryAuditable extends AuditableContract
{
    /**
     * Get the identifier used for creating history versions.
     *
     * This function should return the ID of the main model associated with the current model.
     * For example, in a translation model, it should return the ID of the model that the translation
     * is related to. This ID will be used to group and manage history versions correctly.
     */
    public function getPrimaryModelIdForHistory(): int;
}
