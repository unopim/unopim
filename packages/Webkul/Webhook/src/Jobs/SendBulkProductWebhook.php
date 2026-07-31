<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Webkul\User\Models\AdminProxy;
use Webkul\Webhook\Services\WebhookService;

class SendBulkProductWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $ids
     */
    public function __construct(
        protected array $ids,
        protected $userId,
        protected string $event = WebhookService::EVENT_PRODUCT_UPDATED
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        if ($this->userId && ($user = AdminProxy::find($this->userId))) {
            Auth::login($user);
        }

        if ($this->event === WebhookService::EVENT_PRODUCT_CREATED) {
            $webhookService->sendBatchCreatedByIds($this->ids);

            return;
        }

        $webhookService->sendBatchByIds($this->ids);
    }
}
