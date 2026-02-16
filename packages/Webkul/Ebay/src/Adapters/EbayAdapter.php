<?php

namespace Webkul\Ebay\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class EbayAdapter extends AbstractChannelAdapter
{
    protected const API_BASE = 'https://api.ebay.com';

    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $accessToken = $credentials['access_token'] ?? '';

            if (empty($accessToken)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Access token is required.',
                    errors: ['Missing access_token'],
                );
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get(self::API_BASE.'/sell/inventory/v1/inventory_item', [
                    'limit' => 1,
                ]);

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
                    'marketplace_id' => $credentials['marketplace_id'] ?? 'EBAY_US',
                    'total_items'    => $data['total'] ?? 0,
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
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($accessToken)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['eBay API credentials (access_token) are required'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'ebay'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildEbayBody($localeMappedData);

            $common = $localeMappedData['common'] ?? [];
            $sku = $common['sku'] ?? $product->sku ?? $existingExternalId;

            if (empty($sku)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['SKU is required for eBay listings'],
                );
            }

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Language' => $this->getContentLanguage()])
                ->timeout(30)
                ->put(self::API_BASE.'/sell/inventory/v1/inventory_item/'.urlencode($sku), $body);

            if ($response->failed()) {
                $errorBody = $response->json();

                return new SyncResult(
                    success: false,
                    externalId: $existingExternalId,
                    action: 'failed',
                    errors: [$errorBody['errors'][0]['message'] ?? 'HTTP '.$response->status()],
                );
            }

            return new SyncResult(
                success: true,
                externalId: (string) $sku,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[eBay] syncProduct failed', [
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
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($accessToken)) {
                return null;
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get(self::API_BASE.'/sell/inventory/v1/inventory_item/'.urlencode($externalId));

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return $this->normalizeEbayProduct($data);
        } catch (\Exception $e) {
            Log::warning('[eBay] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($accessToken)) {
                return false;
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->delete(self::API_BASE.'/sell/inventory/v1/inventory_item/'.urlencode($externalId));

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[eBay] deleteProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannelFields(?string $locale = null): array
    {
        return [
            ['code' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false, 'is_translatable' => false],
            ['code' => 'condition', 'label' => 'Condition', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'price', 'label' => 'Price', 'type' => 'price', 'required' => true, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'brand', 'label' => 'Brand', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'mpn', 'label' => 'MPN', 'type' => 'string', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'GBP', 'EUR', 'AUD', 'CAD'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        // eBay uses a subscription API model, not per-event webhook registration.
        // Webhooks are managed through eBay Developer Portal or the Notification API.
        return true;
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Ebay-Signature');
        $payload = $request->getContent();
        $secret = $this->credentials['client_secret'] ?? '';

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $calculated = hash_hmac('sha256', $payload, $secret);

        return hash_equals($calculated, $signature);
    }

    public function refreshCredentials(): ?array
    {
        try {
            $clientId = $this->credentials['client_id'] ?? '';
            $clientSecret = $this->credentials['client_secret'] ?? '';
            $refreshToken = $this->credentials['refresh_token'] ?? '';

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
                return null;
            }

            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30)
                ->post('https://api.ebay.com/identity/v1/oauth2/token', [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->failed()) {
                Log::error('[eBay] Token refresh failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $newAccessToken = $data['access_token'] ?? null;

            if (! $newAccessToken) {
                return null;
            }

            return array_merge($this->credentials, ['access_token' => $newAccessToken]);
        } catch (\Exception $e) {
            Log::error('[eBay] refreshCredentials failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(requestsPerSecond: 5);
    }

    protected function buildEbayBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        // Prefer en_US, fallback through supported locales
        $localeData = $locales['en_US'] ?? $locales['en_GB'] ?? $locales['de_DE'] ?? [];

        $title = $localeData['title'] ?? $localeData['name'] ?? $common['title'] ?? $common['name'] ?? null;
        $description = $localeData['description'] ?? $common['description'] ?? null;

        $product = [];

        if ($title !== null) {
            $product['title'] = $title;
        }

        if ($description !== null) {
            $product['description'] = $description;
        }

        $product['imageUrls'] = [];

        $aspects = [];

        $brand = $common['brand'] ?? $localeData['brand'] ?? null;

        if ($brand !== null) {
            $aspects['Brand'] = [$brand];
        }

        $mpn = $common['mpn'] ?? $localeData['mpn'] ?? null;

        if ($mpn !== null) {
            $aspects['MPN'] = [$mpn];
        }

        if (! empty($aspects)) {
            $product['aspects'] = $aspects;
        }

        $body = [
            'product'      => $product,
            'condition'    => $common['condition'] ?? 'NEW',
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => (int) ($common['quantity'] ?? 0),
                ],
            ],
        ];

        return $body;
    }

    protected function normalizeEbayProduct(array $data): array
    {
        $common = [];
        $product = $data['product'] ?? [];

        if (isset($product['title'])) {
            $common['title'] = $product['title'];
        }

        if (isset($product['description'])) {
            $common['description'] = $product['description'];
        }

        if (isset($data['condition'])) {
            $common['condition'] = $data['condition'];
        }

        if (isset($data['availability']['shipToLocationAvailability']['quantity'])) {
            $common['quantity'] = $data['availability']['shipToLocationAvailability']['quantity'];
        }

        if (isset($data['sku'])) {
            $common['sku'] = $data['sku'];
        }

        $aspects = $product['aspects'] ?? [];

        if (isset($aspects['Brand'][0])) {
            $common['brand'] = $aspects['Brand'][0];
        }

        if (isset($aspects['MPN'][0])) {
            $common['mpn'] = $aspects['MPN'][0];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }

    protected function getContentLanguage(): string
    {
        $marketplaceMap = [
            'EBAY_US' => 'en-US',
            'EBAY_GB' => 'en-GB',
            'EBAY_DE' => 'de-DE',
            'EBAY_FR' => 'fr-FR',
            'EBAY_ES' => 'es-ES',
            'EBAY_AU' => 'en-AU',
            'EBAY_CA' => 'en-CA',
        ];

        $marketplaceId = $this->credentials['marketplace_id'] ?? 'EBAY_US';

        return $marketplaceMap[$marketplaceId] ?? 'en-US';
    }
}
