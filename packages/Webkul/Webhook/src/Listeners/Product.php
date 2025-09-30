<?php

namespace Webkul\Webhook\Listeners;

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
        $code = $product->sku;
        $type = $product->type;

        if ($this->settingsRepository->isWebhookActive()) {
            $this->webhookService->sendDataToWebhook($code, $type);
        }
    }

    public function afterBulkUpdate(array $ids)
    {
        if ($this->settingsRepository->isWebhookActive()) {
            $this->webhookService->sendBatchByIds($ids);
        }
    }
}
