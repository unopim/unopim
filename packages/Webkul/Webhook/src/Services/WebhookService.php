<?php

namespace Webkul\Webhook\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Webhook\Helpers\ProductComparer;
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
    public function sendDataToWebhook(Product $product, array $productChanges = []): ?Response
    {
        $webhookData = [];

        $webhookUrl = $this->settingsRepository->getWebhookUrl();

        if (empty($webhookUrl)) {
            return null;
        }

        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        $timezone = is_array($admin)
            ? ($admin['timezone'] ?? config('app.timezone'))
            : ($admin?->timezone ?? config('app.timezone'));

        $webhookData = [
            'event'     => 'product.updated',
            'timestamp' => now()->toDateTimeString(),
            'user_timezone' => $timezone,
            'data'      => [
                $this->normalizeWebhookData($product, $productChanges),
            ],
        ];

        try {
            $response = Http::post($webhookUrl, $webhookData);
        } catch (\Exception $e) {
            $response = null;

            report($e);
        }

        $this->storeLogs($product->sku, $response?->successful() ? 1 : 0, $this->normalizeResponseForLog($response ?? $e));

        return $response;
    }

    /**
     * Send product.created event to configured webhook URL.
     */
    public function sendCreatedToWebhook(Product $product, array $productChanges = []): ?Response
    {
        $webhookData = [];

        $webhookUrl = $this->settingsRepository->getWebhookUrl();

        if (empty($webhookUrl)) {
            return null;
        }

        // Resolve the acting admin (session or API) so we can include their timezone
        // in the created event payload. Fall back to app timezone when not set.
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        $timezone = is_array($admin)
            ? ($admin['timezone'] ?? config('app.timezone'))
            : ($admin?->timezone ?? config('app.timezone'));

        $webhookData = [
            'event'         => 'product.created',
            'timestamp'     => now()->toDateTimeString(),
            'user_timezone' => $timezone,
            'data'          => [
                $this->normalizeWebhookData($product, $productChanges),
            ],
        ];

        try {
            $response = Http::post($webhookUrl, $webhookData);
        } catch (\Exception $e) {
            $response = null;

            report($e);
        }

        $this->storeLogs($product->sku, $response?->successful() ? 1 : 0, $this->normalizeResponseForLog($response ?? $e));

        return $response;
    }

    /**
     * Send a batch of products to the webhook by their IDs.
     */
    public function sendBatchByIds(array $ids): ?Response
    {
        $products = $this->productRepository->findWhereIn('id', $ids);

        return $this->sendBatch($products);
    }

    /**
     * Send a batch of products to the webhook by their SKUs.
     */
    public function sendBatchBySkus(array $skus): ?Response
    {
        $products = $this->productRepository->findWhereIn('sku', $skus);

        return $this->sendBatch($products);
    }

    /**
     * Internal helper to send a collection of product models as a single batch payload.
     */
    protected function sendBatch($products): ?Response
    {
        $webhookData = [];

        $webhookUrl = $this->settingsRepository->getWebhookUrl();

        if (empty($webhookUrl)) {
            return null;
        }

        $normalized = [];

        foreach ($products as $product) {
            $productChanges = $this->getProductChangesForWebhook($product);

            if (empty($productChanges)) {
                continue;
            }

            $normalized[] = $this->normalizeWebhookData($product, $productChanges);
        }

        if (empty($normalized)) {
            return null;
        }

        $webhookData = [
            'event'     => 'product.updated',
            'timestamp' => now()->toDateTimeString(),
            'data'      => $normalized,
        ];

        try {
            $response = Http::post($webhookUrl, $normalized);
        } catch (\Exception $e) {
            $response = null;

            report($e);
        }

        $status = $response?->successful() ? 1 : 0;

        $this->storeBatchLogs($products, $status, $this->normalizeResponseForLog($response ?? $e));

        return $response;
    }

    protected function storeLogs(string $code, int $status, $response = null): void
    {
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        if (is_array($admin)) {
            $adminName = $admin['name'] ?? null;
        } else {
            $adminName = $admin?->name ?? null;
        }

        $data = [
            'user'   => $adminName,
            'sku'    => $code,
            'status' => $status,
            'extra'  => ['response' => $response],
        ];

        $this->logsRepository->create($data);
    }

    protected function storeBatchLogs($products, int $status, $response = null): void
    {
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        $adminName = is_array($admin) ? ($admin['name'] ?? null) : ($admin?->name ?? null);

        foreach ($products as $product) {
            $data = [
                'user'   => $adminName ?? null,
                'sku'    => $product->sku ?? ($product['sku'] ?? null),
                'status' => $status,
                'extra'  => ['response' => $response],
            ];

            $this->logsRepository->create($data);
        }
    }

    protected function normalizeWebhookData(Product $product, array $productChanges = []): array
    {
        $sku = $product->sku ?? ($product['sku'] ?? null);
        $type = $product->type ?? ($product['type'] ?? null);

        $normalized = [
            'id'      => $product->id,
            'status'  => (bool) ($product->status ?? ($product['status'])),
            'sku'     => $sku,
            'type'    => $type,
            'changes' => $productChanges,
        ];

        if ($type === 'configurable') {
            $normalized['variants'] = $product->variants->map(function ($variant) {
                return [
                    'sku'    => $variant->sku,
                    'status' => (bool) $variant->status,
                ];
            })->toArray();
        }

        return $normalized;
    }

    private function normalizeResponseForLog($response): mixed
    {
        if ($response instanceof Response) {
            return [
                'status' => $response->status(),
                'body'   => $response->body(),
            ];
        }

        if ($response instanceof \Exception) {
            return [
                'status' => $response->getCode(),
                'error'  => $response->getMessage(),
            ];
        }

        return $response;
    }

    public function getProductChangesForWebhook(Product $product): array
    {
        $latestChanges = $product->audits()->latest()->first();

        if (! $latestChanges) {
            return [];
        }

        $changeTime = $latestChanges->updated_at;
        $productTime = $product->updated_at;

        if ($productTime->diffInMinutes(now()) > 60) {
            return [];
        }

        if ($changeTime->diffInSeconds($productTime) > 2) {
            return [];
        }

        $oldRaw = $latestChanges->old_values ?? [];
        $newRaw = $latestChanges->new_values ?? [];

        $diff = ProductComparer::compare($oldRaw, $newRaw);

        if (! empty($diff['added']) || ! empty($diff['removed']) || ! empty($diff['changed'])) {
            return $diff;
        }

        return [];
    }
}
