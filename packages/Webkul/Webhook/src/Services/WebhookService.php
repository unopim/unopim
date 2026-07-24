<?php

namespace Webkul\Webhook\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Webhook\Helpers\ProductComparer;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\WebhookRepository;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/**
 * Sends product data to every active webhook subscribed to the fired event
 * and records a per-webhook delivery log.
 */
class WebhookService
{
    public const EVENT_PRODUCT_CREATED = 'product.created';

    public const EVENT_PRODUCT_UPDATED = 'product.updated';

    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected LogsRepository $logsRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Send a product.updated event to every subscribed webhook.
     */
    public function sendDataToWebhook(Product $product, array $productChanges = []): ?Response
    {
        return $this->fanOutSingle($product, $productChanges, self::EVENT_PRODUCT_UPDATED);
    }

    /**
     * Send a product.created event to every subscribed webhook.
     */
    public function sendCreatedToWebhook(Product $product, array $productChanges = []): ?Response
    {
        return $this->fanOutSingle($product, $productChanges, self::EVENT_PRODUCT_CREATED);
    }

    /**
     * Send a batch of products to every subscribed webhook by their IDs.
     */
    public function sendBatchByIds(array $ids): ?Response
    {
        return $this->sendBatch($this->productRepository->findWhereIn('id', $ids));
    }

    /**
     * Send a batch of products to every subscribed webhook without requiring
     * change detection. Used for bulk-edit where no per-product diff exists.
     */
    public function sendBatchForBulkEdit(array $ids): ?Response
    {
        return $this->sendBatch($this->productRepository->findWhereIn('id', $ids), requireChanges: false);
    }

    /**
     * Send a batch of products to every subscribed webhook by their SKUs.
     */
    public function sendBatchBySkus(array $skus): ?Response
    {
        return $this->sendBatch($this->productRepository->findWhereIn('sku', $skus));
    }

    /**
     * Build a single-product payload and deliver it to each subscribed webhook.
     */
    protected function fanOutSingle(Product $product, array $productChanges, string $event): ?Response
    {
        $webhooks = $this->webhookRepository->getActiveForEvent($event);

        if ($webhooks->isEmpty()) {
            return null;
        }

        $payload = [
            'event'         => $event,
            'timestamp'     => now()->toDateTimeString(),
            'user_timezone' => $this->actingTimezone(),
            'data'          => [
                $this->normalizeWebhookData($product, $productChanges),
            ],
        ];

        $lastResponse = null;

        foreach ($webhooks as $webhook) {
            $lastResponse = $this->deliver($webhook, $payload, $product->sku, $event);
        }

        return $lastResponse;
    }

    /**
     * Internal helper to send a collection of products as a single batch
     * payload to each subscribed webhook.
     *
     * @param  bool  $requireChanges  When false, every product is included
     *                                regardless of whether an audit diff exists.
     */
    protected function sendBatch($products, bool $requireChanges = true): ?Response
    {
        $webhooks = $this->webhookRepository->getActiveForEvent(self::EVENT_PRODUCT_UPDATED);

        if ($webhooks->isEmpty()) {
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
        }

        $payload = [
            'event'     => self::EVENT_PRODUCT_UPDATED,
            'timestamp' => now()->toDateTimeString(),
            'data'      => $normalized,
        ];

        $lastResponse = null;

        foreach ($webhooks as $webhook) {
            $lastResponse = $this->deliver($webhook, $payload, null, self::EVENT_PRODUCT_UPDATED, $products);
        }

        return $lastResponse;
    }

    /**
     * Deliver a payload to a single webhook: SSRF-guard, sign, send, log.
     *
     * @param  Collection|null  $batchProducts  When set, a
     *                                          log row is written per product.
     */
    protected function deliver(Webhook $webhook, array $payload, ?string $sku, string $event, $batchProducts = null): ?Response
    {
        $safety = SafeWebhookUrl::validate($webhook->url);

        if (! $safety['valid']) {
            Log::warning('Webhook dispatch blocked — unsafe URL', [
                'webhook_id' => $webhook->id,
                'reason'     => $safety['reason'],
                'ip'         => $safety['ip'] ?? null,
            ]);

            return null;
        }

        $body = json_encode($payload);
        $exception = null;

        try {
            $response = Http::withOptions(SafeWebhookUrl::httpOptions($webhook->url))
                ->withHeaders($this->buildHeaders($webhook, $event, $body))
                ->withBody($body, 'application/json')
                ->post($webhook->url);
        } catch (\Exception $e) {
            $response = null;
            $exception = $e;

            report($e);
        }

        $status = $response?->successful() ? 1 : 0;
        $httpCode = $response?->status() ?? ($exception?->getCode() ?: null);
        $logResponse = $this->normalizeResponseForLog($response ?? $exception);

        if ($batchProducts !== null) {
            $this->storeBatchLogs($webhook, $batchProducts, $status, $httpCode, $event, $logResponse, $payload);
        } else {
            $this->storeLogs($webhook, $sku, $status, $httpCode, $event, $logResponse, $payload);
        }

        return $response;
    }

    /**
     * Outgoing headers for a delivery: custom headers plus, when a secret is
     * set, an HMAC-SHA256 signature so the receiver can verify authenticity.
     *
     * @return array<string, string>
     */
    protected function buildHeaders(Webhook $webhook, string $event, string $body): array
    {
        $headers = [
            'X-Unopim-Event'      => $event,
            'X-Unopim-Webhook-Id' => (string) $webhook->id,
        ];

        foreach ((array) $webhook->headers as $key => $value) {
            if (is_string($key) && $key !== '') {
                $headers[$key] = (string) $value;
            }
        }

        if (! empty($webhook->secret)) {
            $headers['X-Unopim-Signature'] = 'sha256='.hash_hmac('sha256', $body, (string) $webhook->secret);
        }

        return $headers;
    }

    protected function actingTimezone(): string
    {
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        return is_array($admin)
            ? ($admin['timezone'] ?? config('app.timezone'))
            : ($admin?->timezone ?? config('app.timezone'));
    }

    protected function actingName(): ?string
    {
        $admin = auth('admin')->user()
            ?? auth('api')->user()
            ?? request()->user('admin')
            ?? request()->user('api');

        return is_array($admin) ? ($admin['name'] ?? null) : ($admin?->name ?? null);
    }

    protected function storeLogs(Webhook $webhook, ?string $code, int $status, ?int $httpCode, string $event, $response = null, array $payload = []): void
    {
        $this->logsRepository->create([
            'webhook_id' => $webhook->id,
            'user'       => $this->actingName(),
            'sku'        => $code,
            'event'      => $event,
            'status'     => $status,
            'http_code'  => $httpCode,
            'extra'      => ['payload' => $payload, 'response' => $response],
        ]);
    }

    protected function storeBatchLogs(Webhook $webhook, $products, int $status, ?int $httpCode, string $event, $response = null, array $payload = []): void
    {
        $adminName = $this->actingName();

        // Store only this product's slice per row. Persisting the whole batch
        // payload on every row was O(N²) storage for an N-product bulk edit.
        $dataBySku = collect($payload['data'] ?? [])->keyBy(fn ($entry): ?string => $entry['sku'] ?? null);

        $envelope = array_diff_key($payload, ['data' => true]);

        foreach ($products as $product) {
            $sku = $product->sku ?? ($product['sku'] ?? null);

            $rowPayload = $payload === []
                ? []
                : $envelope + ['data' => array_values(array_filter([$dataBySku->get($sku)]))];

            $this->logsRepository->create([
                'webhook_id' => $webhook->id,
                'user'       => $adminName,
                'sku'        => $sku,
                'event'      => $event,
                'status'     => $status,
                'http_code'  => $httpCode,
                'extra'      => ['payload' => $rowPayload, 'response' => $response],
            ]);
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
            $normalized['variants'] = $product->variants->map(fn ($variant): array => [
                'sku'    => $variant->sku,
                'status' => (bool) $variant->status,
            ])->toArray();
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
