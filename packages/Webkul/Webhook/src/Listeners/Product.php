<?php

namespace Webkul\Webhook\Listeners;

use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\SettingsRepository;
use Webkul\Webhook\Traits\WebhookTrait;

class Product
{
    use WebhookTrait;

    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected LogsRepository $logsRepository
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
        $settings = $this->settingsRepository->getAllDataAndNormalize();

        if ($settings['webhook_active']) {
            $response = $this->sendDataToWebhook($code, $type);

            $this->storeLogs($code, $response->successful() ? 1 : 0);
        }
    }
}
