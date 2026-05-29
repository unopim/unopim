<?php

namespace Webkul\Webhook\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Webhook\Helpers\ProductComparer;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\SettingsRepository;
use Webkul\Webhook\Validators\SafeWebhookUrl;

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

        if (in_array($webhookUrl, [null, '', '0'], true)) {
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
            'event'         => 'product.updated',
            'timestamp'     => now()->toDateTimeString(),
            'user_timezone' => $timezone,
            'data'          => [
                $this->normalizeWebhookData($product, $productChanges),
            ],
        ];

        try {
            $safety = SafeWebhookUrl::validate($webhookUrl);
            if (! $safety['valid']) {
                Log::warning('Webhook dispatch blocked — unsafe URL', [
                    'reason' => $safety['reason'],
                    'ip'     => $safety['ip'] ?? null,
                ]);

                return null;
            }

            $response = Http::withOptions(SafeWebhookUrl::httpOptions($webhookUrl))->post($webhookUrl, $webhookData);
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

        if (in_array($webhookUrl, [null, '', '0'], true)) {
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
            $safety = SafeWebhookUrl::validate($webhookUrl);
            if (! $safety['valid']) {
                Log::warning('Webhook dispatch blocked — unsafe URL', [
                    'reason' => $safety['reason'],
                    'ip'     => $safety['ip'] ?? null,
                ]);

                return null;
            }

            $response = Http::withOptions(SafeWebhookUrl::httpOptions($webhookUrl))->post($webhookUrl, $webhookData);
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
     * Send a batch of products to the webhook without requiring change detection.
     * Used for bulk-edit operations where no per-product audit diff may exist.
     */
    public function sendBatchForBulkEdit(array $ids): ?Response
    {
        $products = $this->productRepository->findWhereIn('id', $ids);

        return $this->sendBatch($products, requireChanges: false);
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
     *
     * @param  bool  $requireChanges  When false, every product is included regardless
     *                                of whether an audit diff was detected (e.g. bulk-edit).
     */
    protected function sendBatch(mixed $products, bool $requireChanges = true): ?Response
    {
        $webhookUrl = $this->settingsRepository->getWebhookUrl();

        if (in_array($webhookUrl, [null, '', '0'], true)) {
            return null;
        }

        $normalized = [];

        foreach ($products as $product) {
            $productChanges = $requireChanges
                ? $this->getProductChangesForWebhook($product)
                : [];

            if ($requireChanges && $productChanges === []) {
                continue;
            }

            $normalized[] = $this->normalizeWebhookData($product, $productChanges);
        }

        if ($normalized === []) {
            return null;
        }[
            'event'     => 'product.updated',
        'timestamp'     => now()->toDateTimeString(),
        'data'          => $normalized,
        ];

        try {
            $safety = SafeWebhookUrl::validate($webhookUrl);
            if (! $safety['valid']) {
                Log::warning('Webhook dispatch blocked — unsafe URL', [
                    'reason' => $safety['reason'],
                    'ip'     => $safety['ip'] ?? null,
                ]);

                return null;
            }

            $response = Http::withOptions(SafeWebhookUrl::httpOptions($webhookUrl))->post($webhookUrl, $normalized);
        } catch (\Exception $e) {
            $response = null;

            report($e);
        }

        $status = $response?->successful() ? 1 : 0;

        $this->storeBatchLogs($products, $status, $this->normalizeResponseForLog($response ?? $e));

        return $response;
    }

    protected function storeLogs(string $code, int $status, mixed $response = null): void
    {
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        $adminName = is_array($admin) ? $admin['name'] ?? null : $admin?->name ?? null;

        $data = [
            'user'   => $adminName,
            'sku'    => $code,
            'status' => $status,
            'extra'  => ['response' => $response],
        ];

        $this->logsRepository->create($data);
    }

    protected function storeBatchLogs(mixed $products, int $status, mixed $response = null): void
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
            $normalized['variants'] = $product->variants->map(fn (mixed $variant) => [
                'sku'    => $variant->sku,
                'status' => (bool) $variant->status,
            ])->toArray();
        }

        return $normalized;
    }

    private function normalizeResponseForLog(mixed $response): mixed
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

        if (abs($productTime->diffInMinutes(now())) > 60) {
            return [];
        }

        if (abs($changeTime->diffInSeconds($productTime)) > 2) {
            return [];
        }

        $oldRaw = $latestChanges->old_values ?? [];
        $newRaw = $latestChanges->new_values ?? [];

        $diff = ProductComparer::compare($oldRaw, $newRaw);

        if ($latestChanges->event === 'created') {
            $product->refresh();

            $diff['added']['sku'] = $product->sku;
            $diff['added']['type'] = $product->type;
            $diff['added']['status'] = (bool) $product->status;

            return $diff;
        }

        if (! empty($diff['added']) || ! empty($diff['removed']) || ! empty($diff['changed'])) {
            return $diff;
        }

        return [];
    }
}
