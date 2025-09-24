<?php

namespace Webkul\Webhook\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webkul\AdminApi\ApiDataSource\Catalog\ConfigurableProductDataSource;
use Webkul\AdminApi\ApiDataSource\Catalog\SimpleProductDataSource;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\SettingsRepository;

/**
 * Service responsible for sending product data to an external webhook and storing logs.
 */
class WebhookService
{
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected LogsRepository $logsRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Send product data to configured webhook URL.
     */
    public function sendDataToWebhook(string $code, string $type, bool $isWebhookBtn = false): Response
    {
        $webhookData = [];

        $settings = $this->settingsRepository->getAllDataAndNormalize();

        $webhookUrl = $settings['webhook_url'] ?? null;

        if (empty($webhookUrl)) {
            return Http::response(['error' => 'webhook_url not configured'], 400);
        }

        $productData = $this->getProductDataByCode($code, $type);

        $webhookData['product'] = $productData;

        $response = Http::post($webhookUrl, $webhookData);

        $this->storeLogs($code, $response->successful() ? 1 : 0);

        return $response;
    }

    /**
     * Send a batch of products to the webhook by their IDs.
     */
    public function sendBatchByIds(array $ids, bool $isWebhookBtn = false): Response
    {
        $products = $this->productRepository->findWhereIn('id', $ids);

        return $this->sendBatch($products, $isWebhookBtn);
    }

    /**
     * Send a batch of products to the webhook by their SKUs.
     */
    public function sendBatchBySkus(array $skus, bool $isWebhookBtn = false): Response
    {
        $products = $this->productRepository->findWhereIn('sku', $skus);

        return $this->sendBatch($products, $isWebhookBtn);
    }

    /**
     * Internal helper to send a collection of product models as a single batch payload.
     */
    protected function sendBatch($products, bool $isWebhookBtn = false): Response
    {
        $webhookData = [];

        $settings = $this->settingsRepository->getAllDataAndNormalize();

        $webhookUrl = $settings['webhook_url'] ?? null;

        if (empty($webhookUrl)) {
            return Http::response(['error' => 'webhook_url not configured'], 400);
        }

        $normalized = [];

        foreach ($products as $product) {
            $normalized[] = $this->normalizeProductModel($product);
        }

        $webhookData['products'] = $normalized;

        $response = Http::post($webhookUrl, $webhookData);

        $status = $response->successful() ? 1 : 0;

        $this->storeBatchLogs($products, $status);

        return $response;
    }

    protected function getProductDataByCode(string $code, string $type): array
    {
        return match ($type) {
            'simple'       => app(SimpleProductDataSource::class)->getByCode($code),
            'configurable' => app(ConfigurableProductDataSource::class)->getByCode($code),
            default        => []
        };
    }

    protected function storeLogs(string $code, int $status): void
    {
        $adminUser = auth('admin')->getUser()->toArray();

        $data = [
            'user'   => $adminUser['name'] ?? null,
            'sku'    => $code,
            'status' => $status,
        ];

        $this->logsRepository->create($data);
    }

    protected function storeBatchLogs($products, int $status): void
    {
        $adminUser = auth('admin')->getUser()->toArray();

        foreach ($products as $product) {
            $data = [
                'user'   => $adminUser['name'] ?? null,
                'sku'    => $product->sku ?? ($product['sku'] ?? null),
                'status' => $status,
            ];

            $this->logsRepository->create($data);
        }
    }

    protected function normalizeProductModel($product): array
    {
        $sku = $product->sku ?? ($product['sku'] ?? null);
        $type = $product->type ?? ($product['type'] ?? null);

        $normalized = [
            'sku'        => $sku,
            'status'     => isset($product->status) ? (bool) $product->status : (bool) ($product['status'] ?? null),
            'parent'     => $product->parent->sku ?? ($product['parent']['sku'] ?? null),
            'family'     => $product->attribute_family->code ?? ($product['attribute_family']['code'] ?? null),
            'type'       => $type,
            'additional' => $product->additional ?? ($product['additional'] ?? null),
            'created_at' => $product->created_at ?? ($product['created_at'] ?? null),
            'updated_at' => $product->updated_at ?? ($product['updated_at'] ?? null),
            'values'     => $product->values ?? ($product['values'] ?? []),
        ];

        if ($type === config('product_types.configurable.key')) {
            try {
                $normalized['super_attributes'] = $this->productRepository->getSuperAttributes($product);
            } catch (\Throwable $e) {
                $normalized['super_attributes'] = [];
            }

            $variants = [];

            if (isset($product->variants) && is_iterable($product->variants)) {
                foreach ($product->variants as $variant) {
                    $variants[] = [
                        'sku' => $variant->sku ?? ($variant['sku'] ?? null),
                    ];
                }
            }

            $normalized['variants'] = $variants;
        }

        return $normalized;
    }
}
