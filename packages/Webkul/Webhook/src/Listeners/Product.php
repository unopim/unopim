<?php

namespace Webkul\Webhook\Listeners;

use Webkul\Webhook\Jobs\SendBulkEditProductWebhook;
use Webkul\Webhook\Jobs\SendBulkProductWebhook;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Repositories\WebhookRepository;
use Webkul\Webhook\Services\WebhookService;

class Product
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected WebhookService $webhookService
    ) {}

    /**
     * Update or create product indices
     */
    public function afterUpdate(\Webkul\Product\Contracts\Product $product): void
    {
        if (! $this->webhookRepository->hasActiveForEvent(WebhookService::EVENT_PRODUCT_UPDATED)) {
            return;
        }

        $changes = $this->webhookService->getProductChangesForWebhook($product);

        if ($changes === []) {
            return;
        }

        dispatch(new SendProductWebhook($product->id, $changes, 'updated', auth('admin')?->user()?->id))->onQueue('webhooks');
    }

    public function afterCreate(\Webkul\Product\Contracts\Product $product): void
    {
        if (! $this->webhookRepository->hasActiveForEvent(WebhookService::EVENT_PRODUCT_CREATED)) {
            return;
        }

        $changes = $this->webhookService->getProductChangesForWebhook($product);

        if ($changes === []) {
            return;
        }

        dispatch(new SendProductWebhook($product->id, $changes, 'created', auth('admin')?->user()?->id))->onQueue('webhooks');
    }

    public function afterBulkUpdate(array $ids, array $createdIds = [], array $updatedIds = []): void
    {
        if ($createdIds === [] && $updatedIds === []) {
            $updatedIds = $ids;
        }

        $this->afterBulkCreate($createdIds);

        $this->afterBulkEditFromImport($updatedIds);
    }

    public function afterBulkCreate(array $ids): void
    {
        $this->dispatchBulkWebhook($ids, WebhookService::EVENT_PRODUCT_CREATED);
    }

    public function afterBulkEditFromImport(array $ids): void
    {
        $this->dispatchBulkWebhook($ids, WebhookService::EVENT_PRODUCT_UPDATED);
    }

    protected function dispatchBulkWebhook(array $ids, string $event): void
    {
        if ($ids === [] || ! $this->webhookRepository->hasActiveForEvent($event)) {
            return;
        }

        dispatch(new SendBulkProductWebhook($ids, auth('admin')?->user()?->id, $event))
            ->onQueue('webhooks');
    }

    /**
     * Fire webhook for all products processed by a bulk-edit save.
     * Unlike afterUpdate, no change-detection audit is required.
     *
     * @param  array<int>  $ids
     */
    public function afterBulkEdit(array $ids): void
    {
        if (! $this->webhookRepository->hasActiveForEvent(WebhookService::EVENT_PRODUCT_UPDATED)) {
            return;
        }

        dispatch(new SendBulkEditProductWebhook($ids, auth('admin')?->user()?->id))->onQueue('webhooks');
    }
}
