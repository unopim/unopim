<?php

namespace Webkul\HistoryControl\Resolvers;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;
use Webkul\HistoryControl\Contracts\HistoryAuditable;

/**
 * Resolver for history_id used for creating versions through trigger
 *
 * For more details regarding resolver check package documentation and config/audit.php
 */
class PrimaryIdResolver implements Resolver
{
    /**
     * adds value to the history_id column
     */
    public static function resolve(Auditable $auditable)
    {
        if (! $auditable instanceof HistoryAuditable) {
            return null;
        }

        return $auditable->getPrimaryModelIdForHistory();
    }
}
