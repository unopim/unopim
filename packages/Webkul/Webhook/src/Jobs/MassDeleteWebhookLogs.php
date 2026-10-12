<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Webhook\Repositories\LogsRepository;

class MassDeleteWebhookLogs implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @param  array<int>  $logIds
     */
    public function __construct(protected array $logIds) {}

    public function handle(LogsRepository $logsRepository): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $logsRepository->massDelete($this->logIds);
    }
}
