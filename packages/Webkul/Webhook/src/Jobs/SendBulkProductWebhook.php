<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\User\Models\AdminProxy;
use Webkul\Webhook\Services\WebhookService;

class SendBulkProductWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TenantAwareJob;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $ids
     * @return void
     */
    public function __construct(protected array $ids, protected $userId)
    {
        $this->captureTenantContext();
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        $user = AdminProxy::find($this->userId);

        Auth::login($user);

        $webhookService->sendBatchByIds($this->ids);
    }
}
