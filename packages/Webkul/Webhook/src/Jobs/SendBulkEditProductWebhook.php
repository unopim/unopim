<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Webkul\User\Models\AdminProxy;
use Webkul\Webhook\Services\WebhookService;

class SendBulkEditProductWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $ids
     */
    public function __construct(protected array $ids, protected $userId) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        $user = AdminProxy::find($this->userId);

        Auth::login($user);

        $webhookService->sendBatchForBulkEdit($this->ids);
    }
}
