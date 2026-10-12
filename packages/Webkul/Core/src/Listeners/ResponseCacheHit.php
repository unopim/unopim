<?php

namespace Webkul\Core\Listeners;

use Webkul\Core\Jobs\UpdateCreateVisitableIndex;

class ResponseCacheHit
{
    /**
     * Handle the Spatie\ResponseCache\Events\ResponseCacheHit event.
     *
     * The spatie/laravel-responsecache package is not installed, so its event
     * class cannot be referenced directly; the listener stays inert until the
     * package is present and the event actually fires.
     */
    public function handle(object $event): void
    {
        $log = visitor()->getLog();

        dispatch(new UpdateCreateVisitableIndex(array_merge($log, [
            'path_info' => $event->request->getPathInfo(),
        ])));
    }
}
