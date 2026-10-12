<?php

namespace Webkul\DataTransfer\Helpers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a liveness timestamp on the job track so a job whose worker died without
 * running its failure handler can be told apart from one that is simply slow.
 */
trait TracksJobHeartbeat
{
    protected ?int $lastHeartbeatAt = null;

    /**
     * Write a heartbeat, throttled to `job_health.heartbeat_interval`.
     *
     * Updated with a bare query rather than the repository so the running job's
     * in-memory track model is not replaced mid-batch, and so no model events
     * fire on what is a pure liveness ping.
     */
    public function heartbeat(bool $force = false): void
    {
        $track = $this->getHeartbeatTrack();

        if (! $track instanceof Model || ! $track->exists) {
            return;
        }

        $now = time();
        $interval = (int) config('job_health.heartbeat_interval', 30);

        if (
            ! $force
            && $this->lastHeartbeatAt !== null
            && $now - $this->lastHeartbeatAt < $interval
        ) {
            return;
        }

        $this->lastHeartbeatAt = $now;

        DB::table($track->getTable())
            ->where('id', $track->getKey())
            ->update(['heartbeat_at' => now()]);
    }

    /**
     * The job track this helper is currently driving.
     */
    abstract protected function getHeartbeatTrack(): mixed;
}
