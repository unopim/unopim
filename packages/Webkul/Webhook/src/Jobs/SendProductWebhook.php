<?php

namespace Webkul\Webhook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Models\ProductProxy;
use Webkul\Webhook\Services\WebhookService;

class SendProductWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $changes
     * @param  string  $eventType  'created' | 'updated'
     */
    public function __construct(
        protected int $productId,
        protected array $changes,
        protected string $eventType
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $product = ProductProxy::find($this->productId);

        if (! $product) {
            return;
        }

        match ($this->eventType) {
            'created' => $webhookService->sendCreatedToWebhook($product, $this->changes),
            'updated' => $webhookService->sendDataToWebhook($product, $this->changes),
            default   => null,
        };
    }
}
