<?php

namespace Webkul\Core\Listeners;

use Spatie\ResponseCache\Events\ResponseCacheHit as ResponseCacheHitEvent;
use Webkul\Core\Jobs\UpdateCreateVisitableIndex;

class ResponseCacheHit
{
    /**
     * @param  \Spatie\ResponseCache\Events\ResponseCacheHit  $request
     * @return void
     */
    public function handle(ResponseCacheHitEvent $event)
    {
        $log = visitor()->getLog();

        UpdateCreateVisitableIndex::dispatch(array_merge($log, [
            'path_info' => $event->request->getPathInfo(),
        ]));
    }
}
