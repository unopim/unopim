<?php

namespace Webkul\Core\Listeners;

use Spatie\ResponseCache\Events\ResponseCacheHit as ResponseCacheHitEvent;
use Webkul\Core\Jobs\UpdateCreateVisitableIndex;

class ResponseCacheHit
{
    /**
     * @param  ResponseCacheHitEvent  $request
     */
    public function handle(ResponseCacheHitEvent $event): void
    {
        $log = visitor()->getLog();

        UpdateCreateVisitableIndex::dispatch(array_merge($log, [
            'path_info' => $event->request->getPathInfo(),
        ]));
    }
}
