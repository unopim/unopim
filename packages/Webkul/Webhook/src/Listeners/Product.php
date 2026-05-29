<?php

namespace Webkul\Webhook\Listeners;

use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Webhook\Jobs\SendBulkProductWebhook;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\SettingsRepository;
use Webkul\Webhook\Services\WebhookService;

class Product
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected LogsRepository $logsRepository,
        protected WebhookService $webhookService
    ) {}

    /**
     * Update or create product indices
     */
    public function afterUpdate(ProductContract $product): void
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $changes = $this->webhookService->getProductChangesForWebhook($product);

        if ($changes === []) {
            return;
        }

        SendProductWebhook::dispatch($product->id, $changes, 'updated', auth('admin')?->user()?->id)->onQueue('webhooks');
    }

    public function afterCreate(ProductContract $product): void
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $changes = $this->webhookService->getProductChangesForWebhook($product);
        if ($changes === []) {
            return;
        }

        SendProductWebhook::dispatch($product->id, $changes, 'created', auth('admin')?->user()?->id)->onQueue('webhooks');
    }

    public function afterBulkUpdate(array $ids): void
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        SendBulkProductWebhook::dispatch($ids, auth('admin')?->user()?->id);
    }

    /**
     * Fire webhook for all products processed by a bulk-edit save.
     * Unlike afterUpdate, no change-detection audit is required.
     *
     * @param  array<int>  $ids
     */
    public function afterBulkEdit(array $ids): void
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $this->webhookService->sendBatchForBulkEdit($ids);
    }
}
