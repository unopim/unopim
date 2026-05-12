<?php

namespace Webkul\Webhook\Listeners;

use Webkul\Webhook\Jobs\SendBulkProductWebhook;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\SettingsRepository;
use Webkul\Webhook\Services\WebhookService;

class Product
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected LogsRepository $logsRepository,
        protected WebhookService $webhookService
    ) {}

    /**
     * Update or create product indices
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterUpdate($product)
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $changes = $this->webhookService->getProductChangesForWebhook($product);

        if (! $changes) {
            return;
        }

        SendProductWebhook::dispatch($product->id, $changes, 'updated', auth('admin')?->user()?->id)->onQueue('webhooks');
    }

    public function afterCreate($product)
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $product->refresh();

        $changes = $this->webhookService->getProductChangesForWebhook($product);

        $changes['added']['sku'] = $product->sku;
        $changes['added']['type'] = $product->type;
        $changes['added']['status'] = (bool) $product->status;

        SendProductWebhook::dispatch($product->id, $changes, 'created', auth('admin')?->user()?->id)->onQueue('webhooks');
    }

    public function afterBulkUpdate(array $ids)
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
    public function afterBulkEdit(array $ids)
    {
        if (! $this->settingsRepository->isWebhookActive()) {
            return;
        }

        $this->webhookService->sendBatchForBulkEdit($ids);
    }
}
