<?php

namespace Webkul\User\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\User\Models\AdminProxy;

class RefreshGravatarPayload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A failed refresh leaves the previous payload in place and is retried on the next stale read,
     * so there is nothing to gain from retrying the job itself.
     */
    public int $tries = 1;

    public function __construct(public readonly string $hash) {}

    public function handle(): void
    {
        AdminProxy::refreshStaleGravatarPayload($this->hash);
    }
}
