<?php

namespace Webkul\DataTransfer\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobTrackProxy;

/**
 * Tells a job that is still working apart from one whose worker died.
 *
 * A job killed by OOM, SIGKILL or a request timeout never reaches its `failed()`
 * handler, so its track keeps whatever active state it held. Left alone it polls
 * forever on the tracker screen and blocks the profile behind a job that is gone.
 */
class JobHealth
{
    /**
     * States held only while a worker is actively driving the job.
     *
     * `pending` is excluded because a queued job legitimately waits on a busy
     * worker, and `paused` because it is idle on purpose.
     */
    public const ACTIVE_STATES = [
        Import::STATE_VALIDATING,
        Import::STATE_VALIDATED,
        Import::STATE_PROCESSING,
        Import::STATE_PROCESSED,
        Import::STATE_LINKING,
        Import::STATE_LINKED,
        Import::STATE_INDEXING,
        Import::STATE_INDEXED,
    ];

    public function stallTimeout(): int
    {
        return max(60, (int) config('job_health.stall_timeout', 900));
    }

    public function stalledBefore(): Carbon
    {
        return now()->subSeconds($this->stallTimeout());
    }

    /**
     * Tracks that stopped reporting in while claiming to be active.
     *
     * A null heartbeat means the job predates heartbeat tracking, so it is left
     * alone rather than failed retroactively on the strength of a missing column.
     */
    public function stalled(): Builder
    {
        return JobTrackProxy::modelClass()::query()
            ->whereIn('state', self::ACTIVE_STATES)
            ->whereNotNull('heartbeat_at')
            ->where('heartbeat_at', '<', $this->stalledBefore());
    }

    public function isStalled(JobTrackContract $track): bool
    {
        return in_array($track->state, self::ACTIVE_STATES, true)
            && $track->heartbeat_at !== null
            && $track->heartbeat_at->lt($this->stalledBefore());
    }

    /**
     * Fail every stalled track and return how many were reaped.
     */
    public function reap(): int
    {
        $reaped = 0;

        $this->stalled()->each(function (JobTrackContract $track) use (&$reaped): void {
            $this->fail($track);

            $reaped++;
        });

        return $reaped;
    }

    public function fail(JobTrackContract $track): void
    {
        $message = trans('data_transfer::app.job.stalled', [
            'minutes' => (int) ceil($this->stallTimeout() / 60),
        ]);

        $track->forceFill([
            'state'        => Import::STATE_FAILED,
            'errors'       => array_merge((array) $track->errors, [$message]),
            'completed_at' => now(),
        ])->save();

        JobLogger::make($track->id)->error($message);
    }
}
