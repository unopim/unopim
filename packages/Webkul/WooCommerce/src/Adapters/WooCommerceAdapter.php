<?php

namespace Webkul\WooCommerce\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class WooCommerceAdapter extends AbstractChannelAdapter
{
    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $storeUrl = $credentials['store_url'] ?? '';
            $consumerKey = $credentials['consumer_key'] ?? '';
            $consumerSecret = $credentials['consumer_secret'] ?? '';

            if (empty($storeUrl)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Store URL is required.',
                    errors: ['Missing store URL'],
                );
            }

            if (empty($consumerKey) || empty($consumerSecret)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Consumer key and consumer secret are required.',
                    errors: ['Missing consumer key or consumer secret'],
                );
            }

            $baseUrl = rtrim($storeUrl, '/').'/wp-json/wc/v3';

            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout(30)
                ->get($baseUrl.'/products', ['per_page' => 1]);

            if ($response->failed()) {
                return new ConnectionResult(
                    success: false,
                    message: 'Connection failed: HTTP '.$response->status(),
                    errors: [$response->body()],
                );
            }

            $headers = $response->headers();

            return new ConnectionResult(
                success: true,
                message: 'Connection verified successfully.',
                channelInfo: [
                    'store_name'    => 'WooCommerce Store',
                    'product_count' => (int) ($headers['X-WP-Total'][0] ?? 0),
                ],
            );
        } catch (\Exception $e) {
            return new ConnectionResult(
                success: false,
                message: 'Connection failed: '.$e->getMessage(),
                errors: [$e->getMessage()],
            );
        }
    }

    public function syncProduct(Product $product, array $localeMappedData): SyncResult
    {
        try {
            $storeUrl = $this->credentials['store_url'] ?? '';
            $consumerKey = $this->credentials['consumer_key'] ?? '';
            $consumerSecret = $this->credentials['consumer_secret'] ?? '';

            if (empty($storeUrl) || empty($consumerKey) || empty($consumerSecret)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['WooCommerce API credentials (store_url, consumer_key, consumer_secret) are required'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'woocommerce'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildWooCommerceBody($localeMappedData);
            $baseUrl = rtrim($storeUrl, '/').'/wp-json/wc/v3';

            if ($existingExternalId) {
                $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                    ->timeout(30)
                    ->put($baseUrl.'/products/'.$existingExternalId, $body);
            } else {
                $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                    ->timeout(30)
                    ->post($baseUrl.'/products', $body);
            }

            if ($response->failed()) {
                $errorBody = $response->json();

                return new SyncResult(
                    success: false,
                    externalId: $existingExternalId,
                    action: 'failed',
                    errors: [$errorBody['message'] ?? 'HTTP '.$response->status()],
                );
            }

            $data = $response->json();
            $externalId = (string) ($data['id'] ?? $existingExternalId ?? '');

            return new SyncResult(
                success: true,
                externalId: $externalId,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[WooCommerce] syncProduct failed', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return new SyncResult(
                success: false,
                action: 'failed',
                errors: [$e->getMessage()],
            );
        }
    }

    public function fetchProduct(string $externalId, ?string $locale = null): ?array
    {
        try {
            $storeUrl = $this->credentials['store_url'] ?? '';
            $consumerKey = $this->credentials['consumer_key'] ?? '';
            $consumerSecret = $this->credentials['consumer_secret'] ?? '';

            if (empty($storeUrl) || empty($consumerKey) || empty($consumerSecret)) {
                return null;
            }

            $baseUrl = rtrim($storeUrl, '/').'/wp-json/wc/v3';

            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout(30)
                ->get($baseUrl.'/products/'.$externalId);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return $this->normalizeWooCommerceProduct($data);
        } catch (\Exception $e) {
            Log::warning('[WooCommerce] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $storeUrl = $this->credentials['store_url'] ?? '';
            $consumerKey = $this->credentials['consumer_key'] ?? '';
            $consumerSecret = $this->credentials['consumer_secret'] ?? '';

            if (empty($storeUrl) || empty($consumerKey) || empty($consumerSecret)) {
                return false;
            }

            $baseUrl = rtrim($storeUrl, '/').'/wp-json/wc/v3';

            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout(30)
                ->delete($baseUrl.'/products/'.$externalId, ['force' => true]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[WooCommerce] deleteProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannelFields(?string $locale = null): array
    {
        return [
            ['code' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true, 'is_translatable' => true],
            ['code' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true, 'is_translatable' => true],
            ['code' => 'short_description', 'label' => 'Short Description', 'type' => 'text', 'required' => false, 'is_translatable' => true],
            ['code' => 'regular_price', 'label' => 'Regular Price', 'type' => 'price', 'required' => true, 'is_translatable' => false],
            ['code' => 'sale_price', 'label' => 'Sale Price', 'type' => 'price', 'required' => false, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'manage_stock', 'label' => 'Manage Stock', 'type' => 'boolean', 'required' => false, 'is_translatable' => false],
            ['code' => 'stock_quantity', 'label' => 'Stock Quantity', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'weight', 'label' => 'Weight', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'status', 'label' => 'Status', 'type' => 'string', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['en'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $storeUrl = $this->credentials['store_url'] ?? '';
            $consumerKey = $this->credentials['consumer_key'] ?? '';
            $consumerSecret = $this->credentials['consumer_secret'] ?? '';

            if (empty($storeUrl) || empty($consumerKey) || empty($consumerSecret)) {
                return false;
            }

            $topicMap = [
                'product.created' => 'product.created',
                'product.updated' => 'product.updated',
                'product.deleted' => 'product.deleted',
            ];

            $allSuccess = true;
            $baseUrl = rtrim($storeUrl, '/').'/wp-json/wc/v3';
            $webhookSecret = $this->credentials['webhook_secret'] ?? '';

            foreach ($events as $event) {
                $topic = $topicMap[$event] ?? $event;

                $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                    ->timeout(30)
                    ->post($baseUrl.'/webhooks', [
                        'topic'        => $topic,
                        'delivery_url' => $callbackUrl,
                        'secret'       => $webhookSecret,
                    ]);

                if ($response->failed()) {
                    Log::warning('[WooCommerce] Webhook registration failed', [
                        'event'  => $event,
                        'status' => $response->status(),
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[WooCommerce] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        $payload = $request->getContent();
        $secret = $this->credentials['webhook_secret'] ?? '';

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($calculated, $signature);
    }

    public function refreshCredentials(): ?array
    {
        return null;
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(requestsPerSecond: 5);
    }

    protected function buildWooCommerceBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $body = [];

        // Name: prefer English locale, fallback to common
        $name = $locales['en']['name'] ?? $common['name'] ?? null;
        if ($name !== null) {
            $body['name'] = $name;
        }

        // Description
        $description = $locales['en']['description'] ?? $common['description'] ?? null;
        if ($description !== null) {
            $body['description'] = $description;
        }

        // Short description
        $shortDescription = $locales['en']['short_description'] ?? $common['short_description'] ?? null;
        if ($shortDescription !== null) {
            $body['short_description'] = $shortDescription;
        }

        if (isset($common['regular_price'])) {
            $body['regular_price'] = (string) $common['regular_price'];
        }

        if (isset($common['sale_price'])) {
            $body['sale_price'] = (string) $common['sale_price'];
        }

        if (isset($common['sku'])) {
            $body['sku'] = $common['sku'];
        }

        if (isset($common['manage_stock'])) {
            $body['manage_stock'] = (bool) $common['manage_stock'];
        }

        if (isset($common['stock_quantity'])) {
            $body['stock_quantity'] = (int) $common['stock_quantity'];
        }

        if (isset($common['weight'])) {
            $body['weight'] = (string) $common['weight'];
        }

        // Status mapping: active->publish, draft->draft, archived->pending
        if (isset($common['status'])) {
            $statusMap = [
                'active'   => 'publish',
                'draft'    => 'draft',
                'archived' => 'pending',
            ];
            $body['status'] = $statusMap[$common['status']] ?? $common['status'];
        }

        return $body;
    }

    protected function normalizeWooCommerceProduct(array $product): array
    {
        $common = [];

        if (isset($product['name'])) {
            $common['name'] = $product['name'];
        }

        if (isset($product['description'])) {
            $common['description'] = $product['description'];
        }

        if (isset($product['short_description'])) {
            $common['short_description'] = $product['short_description'];
        }

        if (isset($product['regular_price'])) {
            $common['regular_price'] = $product['regular_price'];
        }

        if (isset($product['sale_price'])) {
            $common['sale_price'] = $product['sale_price'];
        }

        if (isset($product['sku'])) {
            $common['sku'] = $product['sku'];
        }

        if (isset($product['manage_stock'])) {
            $common['manage_stock'] = $product['manage_stock'];
        }

        if (isset($product['stock_quantity'])) {
            $common['stock_quantity'] = $product['stock_quantity'];
        }

        if (isset($product['weight'])) {
            $common['weight'] = $product['weight'];
        }

        // Status mapping: publish->active, draft->draft, pending->archived
        if (isset($product['status'])) {
            $statusMap = [
                'publish' => 'active',
                'draft'   => 'draft',
                'pending' => 'archived',
            ];
            $common['status'] = $statusMap[$product['status']] ?? $product['status'];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }
}
