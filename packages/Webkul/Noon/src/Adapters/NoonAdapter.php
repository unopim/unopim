<?php

namespace Webkul\Noon\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class NoonAdapter extends AbstractChannelAdapter
{
    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $apiKey = $credentials['api_key'] ?? '';
            $apiSecret = $credentials['api_secret'] ?? '';
            $apiBase = $credentials['api_base'] ?? 'https://api.noon.partners/v1';

            if (empty($apiKey) || empty($apiSecret)) {
                return new ConnectionResult(
                    success: false,
                    message: 'API key and API secret are required.',
                    errors: ['Missing api_key or api_secret'],
                );
            }

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'X-Api-Secret' => $apiSecret,
            ])
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
                    'store_name'    => $data['store_name'] ?? 'Noon Store',
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
            $apiSecret = $this->credentials['api_secret'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? 'https://api.noon.partners/v1';

            if (empty($apiKey) || empty($apiSecret)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['Noon API credentials (api_key, api_secret) are required'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'noon'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildNoonBody($localeMappedData);
            $baseUrl = rtrim($apiBase, '/');

            if ($existingExternalId) {
                $response = Http::withHeaders([
                    'X-Api-Key'    => $apiKey,
                    'X-Api-Secret' => $apiSecret,
                ])
                    ->timeout(30)
                    ->put($baseUrl.'/products/'.$existingExternalId, $body);
            } else {
                $response = Http::withHeaders([
                    'X-Api-Key'    => $apiKey,
                    'X-Api-Secret' => $apiSecret,
                ])
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

            return new SyncResult(
                success: true,
                externalId: $externalId,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[Noon] syncProduct failed', [
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
            $apiSecret = $this->credentials['api_secret'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? 'https://api.noon.partners/v1';

            if (empty($apiKey) || empty($apiSecret)) {
                return null;
            }

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'X-Api-Secret' => $apiSecret,
            ])
                ->timeout(30)
                ->get(rtrim($apiBase, '/').'/products/'.$externalId);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $product = $data['data'] ?? $data;

            return $this->normalizeNoonProduct($product);
        } catch (\Exception $e) {
            Log::warning('[Noon] fetchProduct failed', [
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
            $apiSecret = $this->credentials['api_secret'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? 'https://api.noon.partners/v1';

            if (empty($apiKey) || empty($apiSecret)) {
                return false;
            }

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'X-Api-Secret' => $apiSecret,
            ])
                ->timeout(30)
                ->delete(rtrim($apiBase, '/').'/products/'.$externalId);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Noon] deleteProduct failed', [
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
            ['code' => 'partner_sku', 'label' => 'Partner SKU', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'barcode', 'label' => 'Barcode', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'brand', 'label' => 'Brand', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'category_path', 'label' => 'Category Path', 'type' => 'string', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['ar', 'en'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['AED', 'SAR', 'EGP'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $apiKey = $this->credentials['api_key'] ?? '';
            $apiSecret = $this->credentials['api_secret'] ?? '';
            $apiBase = $this->credentials['api_base'] ?? 'https://api.noon.partners/v1';

            if (empty($apiKey) || empty($apiSecret)) {
                return false;
            }

            $allSuccess = true;
            $baseUrl = rtrim($apiBase, '/');

            foreach ($events as $event) {
                $response = Http::withHeaders([
                    'X-Api-Key'    => $apiKey,
                    'X-Api-Secret' => $apiSecret,
                ])
                    ->timeout(30)
                    ->post($baseUrl.'/webhooks', [
                        'event' => $event,
                        'url'   => $callbackUrl,
                    ]);

                if ($response->failed()) {
                    Log::warning('[Noon] Webhook registration failed', [
                        'event'  => $event,
                        'status' => $response->status(),
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[Noon] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Noon-Signature');
        $payload = $request->getContent();
        $secret = $this->credentials['api_secret'] ?? '';

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
        return new RateLimitConfig(requestsPerSecond: 2);
    }

    protected function buildNoonBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $body = [];

        // Name: prefer Arabic, fallback to common, fallback to English
        $name = $locales['ar']['name'] ?? $common['name'] ?? $locales['en']['name'] ?? null;
        if ($name !== null) {
            $body['name'] = $name;
        }

        // Description: prefer Arabic, fallback to common, fallback to English
        $description = $locales['ar']['description'] ?? $common['description'] ?? $locales['en']['description'] ?? null;
        if ($description !== null) {
            $body['description'] = $description;
        }

        if (isset($common['price'])) {
            $body['price'] = (float) $common['price'];
        }

        if (isset($common['partner_sku'])) {
            $body['partner_sku'] = $common['partner_sku'];
        }

        if (isset($common['barcode'])) {
            $body['barcode'] = $common['barcode'];
        }

        if (isset($common['brand'])) {
            $body['brand'] = $common['brand'];
        }

        if (isset($common['category_path'])) {
            $body['category_path'] = $common['category_path'];
        }

        return $body;
    }

    protected function normalizeNoonProduct(array $product): array
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

        if (isset($product['partner_sku'])) {
            $common['partner_sku'] = $product['partner_sku'];
        }

        if (isset($product['barcode'])) {
            $common['barcode'] = $product['barcode'];
        }

        if (isset($product['brand'])) {
            $common['brand'] = $product['brand'];
        }

        if (isset($product['category_path'])) {
            $common['category_path'] = $product['category_path'];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }
}
