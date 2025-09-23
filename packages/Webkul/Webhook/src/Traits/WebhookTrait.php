<?php

namespace Webkul\Webhook\Traits;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webkul\AdminApi\ApiDataSource\Catalog\ConfigurableProductDataSource;
use Webkul\AdminApi\ApiDataSource\Catalog\SimpleProductDataSource;

/**
 * trait used to webhook data.
 */
trait WebhookTrait
{
    protected function sendDataToWebhook($code, $type, $isWebhookBtn = false): Response
    {
        $adminUser = auth('admin')->getUser()->toArray();

        if ($isWebhookBtn) {
            $webhookData['userId'] = $adminUser['id'];
        }

        $productData = $this->getProductDataByCode($code, $type);
        $webhookData['product'] = $productData;

        $settings = $this->settingsRepository->getAllDataAndNormalize();

        if (isset($settings['webhook_url'])) {
            $webhookUrl = $settings['webhook_url'];
        }

        $response = Http::post($webhookUrl, $webhookData);

        return $response;

    }

    protected function getProductDataByCode(string $code, string $type): array
    {
        $productData = [];

        switch ($type) {
            case 'simple':
                $productData = app(SimpleProductDataSource::class)->getByCode($code);
                break;
            case 'configurable':
                $productData = app(ConfigurableProductDataSource::class)->getByCode($code);
                break;
        }

        return $productData;
    }

    protected function storeLogs(string $code, int $status)
    {
        $adminUser = auth('admin')->getUser()->toArray();

        $data = [
            'user'   => $adminUser['name'],
            'sku'    => $code,
            'status' => $status,
        ];

        $this->logsRepository->create($data);
    }
}
