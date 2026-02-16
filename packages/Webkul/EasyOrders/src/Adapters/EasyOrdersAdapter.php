<?php

namespace Webkul\EasyOrders\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class EasyOrdersAdapter extends AbstractChannelAdapter
{
    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $apiKey = $credentials['api_key'] ?? '';
            $apiBase = $credentials['api_base'] ?? '';

            if (empty($apiKey)) {
                return new ConnectionResult(
                    success: false,
                    message: 'API key is required.',
                    errors: ['Missing API key'],
                );
            }

            if (empty($apiBase)) {
                return new ConnectionResult(
                    success: false,
                    message: 'API base URL is required.',
                    errors: ['Missing API base URL'],
                );
            }

            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(30)
                ->get(rtrim($apiBase, '/').'/products', ['limit' => 1]);

            if ($response->failed()) {
                return new ConnectionResult(
                    success: false,
                    message: 'Connection failed: HTTP '.$response->status(),
                    errors: [$response->body()],
                );
            }

            $data = $response->json();

            return new ConnectionResult(
                success: true,
                message: 'Connection verified successfully.',
                channelInfo: [
                    'store_name'    => $data['store_name'] ?? 'Easy Orders Store',
                    'product_count' => $data['total'] ?? 0,
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
            $apiKey = $this->credentials['api_key'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? '';

            if (empty($apiKey) || empty($apiBase)) {
                Log::warning('[EasyOrders] Missing API credentials for sync', [
                    'product_id' => $product->id,
                ]);

                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['EasyOrders API credentials (api_key, api_base) are required'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'easy_orders'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildEasyOrdersBody($localeMappedData);
            $baseUrl = rtrim($apiBase, '/');

            if ($existingExternalId) {
                $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                    ->timeout(30)
                    ->put($baseUrl.'/products/'.$existingExternalId, $body);
            } else {
                $response = Http::withHeaders(['X-Api-Key' => $apiKey])
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
            $externalId = (string) ($data['data']['id'] ?? $data['id'] ?? $existingExternalId ?? '');

            Log::info('[EasyOrders] Product synced', [
                'product_id'  => $product->id,
                'external_id' => $externalId,
                'action'      => $existingExternalId ? 'updated' : 'created',
            ]);

            return new SyncResult(
                success: true,
                externalId: $externalId,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[EasyOrders] syncProduct failed', [
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
            $apiKey = $this->credentials['api_key'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? '';

            if (empty($apiKey) || empty($apiBase)) {
                Log::warning('[EasyOrders] Missing API credentials for fetch', [
                    'external_id' => $externalId,
                ]);

                return null;
            }

            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(30)
                ->get(rtrim($apiBase, '/').'/products/'.$externalId);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $product = $data['data'] ?? $data;

            Log::info('[EasyOrders] Product fetched', ['external_id' => $externalId]);

            return $this->normalizeEasyOrdersProduct($product);
        } catch (\Exception $e) {
            Log::warning('[EasyOrders] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $apiKey = $this->credentials['api_key'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? '';

            if (empty($apiKey) || empty($apiBase)) {
                Log::warning('[EasyOrders] Missing API credentials for delete', [
                    'external_id' => $externalId,
                ]);

                return false;
            }

            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(30)
                ->delete(rtrim($apiBase, '/').'/products/'.$externalId);

            if ($response->successful()) {
                Log::info('[EasyOrders] Product deleted', ['external_id' => $externalId]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[EasyOrders] deleteProduct failed', [
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
            ['code' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false, 'is_translatable' => true],
            ['code' => 'price', 'label' => 'Price', 'type' => 'price', 'required' => true, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'commission_rate', 'label' => 'Commission Rate', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'commission_amount', 'label' => 'Commission Amount', 'type' => 'price', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['ar', 'en'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['SAR', 'USD'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $apiKey = $this->credentials['api_key'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? '';

            if (empty($apiKey) || empty($apiBase)) {
                return false;
            }

            $allSuccess = true;
            $baseUrl = rtrim($apiBase, '/');

            foreach ($events as $event) {
                $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                    ->timeout(30)
                    ->post($baseUrl.'/webhooks', [
                        'event' => $event,
                        'url'   => $callbackUrl,
                    ]);

                if ($response->failed()) {
                    Log::warning('[EasyOrders] Webhook registration failed', [
                        'event'  => $event,
                        'status' => $response->status(),
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[EasyOrders] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-EasyOrders-Signature');
        $payload = $request->getContent();
        $secret = $this->credentials['webhook_secret'] ?? '';

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $calculated = hash_hmac('sha256', $payload, $secret);

        return hash_equals($calculated, $signature);
    }

    public function refreshCredentials(): ?array
    {
        return null;
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(requestsPerSecond: 1);
    }

    protected function buildEasyOrdersBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $body = [];

        // Find first matching locale by prefix (supports ar_AE, en_US, etc.)
        $arData = collect($locales)->first(fn ($v, $k) => str_starts_with($k, 'ar')) ?? [];
        $enData = collect($locales)->first(fn ($v, $k) => str_starts_with($k, 'en')) ?? [];

        // Name: prefer Arabic, fallback to common, fallback to English
        $name = $arData['name'] ?? $common['name'] ?? $enData['name'] ?? null;
        if ($name !== null) {
            $body['name'] = $name;
        }

        // Description
        $description = $arData['description'] ?? $common['description'] ?? $enData['description'] ?? null;
        if ($description !== null) {
            $body['description'] = $description;
        }

        if (isset($common['price'])) {
            $body['price'] = (float) $common['price'];
        }

        if (isset($common['sku'])) {
            $body['sku'] = $common['sku'];
        }

        if (isset($common['commission_rate'])) {
            $body['commission_rate'] = (float) $common['commission_rate'];
        }

        if (isset($common['commission_amount'])) {
            $body['commission_amount'] = (float) $common['commission_amount'];
        }

        return $body;
    }

    protected function normalizeEasyOrdersProduct(array $product): array
    {
        $common = [];

        if (isset($product['name'])) {
            $common['name'] = $product['name'];
        }

        if (isset($product['description'])) {
            $common['description'] = $product['description'];
        }

        if (isset($product['price'])) {
            $common['price'] = $product['price'];
        }

        if (isset($product['sku'])) {
            $common['sku'] = $product['sku'];
        }

        if (isset($product['commission_rate'])) {
            $common['commission_rate'] = $product['commission_rate'];
        }

        if (isset($product['commission_amount'])) {
            $common['commission_amount'] = $product['commission_amount'];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }
}
