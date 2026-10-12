<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Webkul\Product\Models\ProductProxy;
use Webkul\User\Models\AdminProxy;
use Webkul\Webhook\Services\WebhookService;

class SendProductWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $changes
     * @param  string  $eventType  'created' | 'updated'
     */
    public function __construct(
        protected int $productId,
        protected array $changes,
        protected string $eventType,
        protected ?int $userId = null,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $product = ProductProxy::find($this->productId);

        if (! $product) {
            return;
        }

        if ($this->userId && ($admin = AdminProxy::find($this->userId))) {
            Auth::login($admin);
        }

        match ($this->eventType) {
            'created' => $webhookService->sendCreatedToWebhook($product, $this->changes),
            'updated' => $webhookService->sendDataToWebhook($product, $this->changes),
            default   => null,
        };
    }
}
