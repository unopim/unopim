<?php

namespace Webkul\Amazon\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class AmazonAdapter extends AbstractChannelAdapter
{
    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $marketplaceId = $credentials['marketplace_id'] ?? '';
            $region = $credentials['region'] ?? 'us-east-1';

            if (empty($credentials['seller_id']) || empty($marketplaceId)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Seller ID and Marketplace ID are required.',
                    errors: ['Missing seller_id or marketplace_id'],
                );
            }

            $accessToken = $this->getAccessToken($credentials);

            if (! $accessToken) {
                return new ConnectionResult(
                    success: false,
                    message: 'Failed to obtain access token.',
                    errors: ['Could not authenticate with Amazon SP-API'],
                );
            }

            $baseUrl = $this->getApiBase($region);

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($baseUrl.'/catalog/2022-04-01/items', [
                    'identifiersType' => 'SKU',
                    'identifiers'     => 'test',
                    'marketplaceIds'  => $marketplaceId,
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
                    'marketplace_id' => $marketplaceId,
                    'seller_id'      => $credentials['seller_id'],
                    'region'         => $region,
                    'item_count'     => $data['numberOfResults'] ?? 0,
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
            $sellerId = $this->credentials['seller_id'] ?? '';
            $marketplaceId = $this->credentials['marketplace_id'] ?? '';
            $region = $this->credentials['region'] ?? 'us-east-1';

            if (empty($sellerId) || empty($marketplaceId)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['Amazon SP-API credentials (seller_id, marketplace_id) are required'],
                );
            }

            $accessToken = $this->getAccessToken($this->credentials);

            if (! $accessToken) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['Failed to obtain Amazon access token'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'amazon'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildAmazonBody($localeMappedData);
            $baseUrl = $this->getApiBase($region);

            $common = $localeMappedData['common'] ?? [];
            $sku = $common['sku'] ?? $product->sku ?? $existingExternalId;

            if (empty($sku)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['SKU is required for Amazon listings'],
                );
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->put($baseUrl.'/listings/2021-08-01/items/'.$sellerId.'/'.urlencode($sku), [
                    'productType'  => $body['productType'] ?? 'PRODUCT',
                    'requirements' => 'LISTING',
                    'attributes'   => $body['attributes'] ?? [],
                ]);

            if ($response->failed()) {
                $errorBody = $response->json();

                return new SyncResult(
                    success: false,
                    externalId: $existingExternalId,
                    action: 'failed',
                    errors: [$errorBody['errors'][0]['message'] ?? 'HTTP '.$response->status()],
                );
            }

            $data = $response->json();

            return new SyncResult(
                success: true,
                externalId: (string) $sku,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[Amazon] syncProduct failed', [
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
            $marketplaceId = $this->credentials['marketplace_id'] ?? '';
            $region = $this->credentials['region'] ?? 'us-east-1';

            if (empty($marketplaceId)) {
                return null;
            }

            $accessToken = $this->getAccessToken($this->credentials);

            if (! $accessToken) {
                return null;
            }

            $baseUrl = $this->getApiBase($region);

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($baseUrl.'/catalog/2022-04-01/items/'.urlencode($externalId), [
                    'marketplaceIds' => $marketplaceId,
                    'includedData'   => 'attributes,summaries',
                ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return $this->normalizeAmazonProduct($data);
        } catch (\Exception $e) {
            Log::warning('[Amazon] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $sellerId = $this->credentials['seller_id'] ?? '';
            $marketplaceId = $this->credentials['marketplace_id'] ?? '';
            $region = $this->credentials['region'] ?? 'us-east-1';

            if (empty($sellerId) || empty($marketplaceId)) {
                return false;
            }

            $accessToken = $this->getAccessToken($this->credentials);

            if (! $accessToken) {
                return false;
            }

            $baseUrl = $this->getApiBase($region);

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->delete($baseUrl.'/listings/2021-08-01/items/'.$sellerId.'/'.urlencode($externalId), [
                    'marketplaceIds' => $marketplaceId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Amazon] deleteProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannelFields(?string $locale = null): array
    {
        return [
            ['code' => 'item_name', 'label' => 'Item Name', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'product_description', 'label' => 'Product Description', 'type' => 'text', 'required' => false, 'is_translatable' => false],
            ['code' => 'bullet_point', 'label' => 'Bullet Point', 'type' => 'text', 'required' => false, 'is_translatable' => false],
            ['code' => 'brand', 'label' => 'Brand', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'manufacturer', 'label' => 'Manufacturer', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'price', 'label' => 'Price', 'type' => 'price', 'required' => true, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => true, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['en_US', 'ar_AE', 'en_GB', 'de_DE', 'fr_FR', 'es_ES'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'AED', 'GBP', 'EUR', 'SAR'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $region = $this->credentials['region'] ?? 'us-east-1';

            $accessToken = $this->getAccessToken($this->credentials);

            if (! $accessToken) {
                return false;
            }

            $baseUrl = $this->getApiBase($region);
            $allSuccess = true;

            foreach ($events as $notificationType) {
                $response = Http::withToken($accessToken)
                    ->timeout(30)
                    ->post($baseUrl.'/notifications/v1/subscriptions/'.$notificationType, [
                        'payloadVersion' => '1.0',
                        'destinationId'  => $callbackUrl,
                    ]);

                if ($response->failed()) {
                    Log::warning('[Amazon] Webhook registration failed', [
                        'notification_type' => $notificationType,
                        'status'            => $response->status(),
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[Amazon] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Amz-Signature');
        $payload = $request->getContent();
        $secret = $this->credentials['secret_key'] ?? '';

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $calculated = hash_hmac('sha256', $payload, $secret);

        return hash_equals($calculated, $signature);
    }

    public function refreshCredentials(): ?array
    {
        $accessToken = $this->getAccessToken($this->credentials, forceRefresh: true);

        if (! $accessToken) {
            return null;
        }

        return array_merge($this->credentials, ['cached_access_token' => $accessToken]);
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(requestsPerSecond: 5);
    }

    protected function buildAmazonBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $attributes = [];
        $languageTag = 'en_US';

        // Determine language tag from available locales
        foreach (['en_US', 'ar_AE', 'en_GB', 'de_DE', 'fr_FR', 'es_ES'] as $tag) {
            if (isset($locales[$tag])) {
                $languageTag = $tag;

                break;
            }
        }

        $name = $locales[$languageTag]['item_name']
            ?? $locales[$languageTag]['name']
            ?? $common['item_name']
            ?? $common['name']
            ?? null;

        if ($name !== null) {
            $attributes['item_name'] = [['value' => $name, 'language_tag' => $languageTag]];
        }

        $description = $locales[$languageTag]['product_description']
            ?? $locales[$languageTag]['description']
            ?? $common['product_description']
            ?? $common['description']
            ?? null;

        if ($description !== null) {
            $attributes['product_description'] = [['value' => $description, 'language_tag' => $languageTag]];
        }

        $bulletPoint = $locales[$languageTag]['bullet_point']
            ?? $common['bullet_point']
            ?? null;

        if ($bulletPoint !== null) {
            $attributes['bullet_point'] = [['value' => $bulletPoint, 'language_tag' => $languageTag]];
        }

        $brand = $common['brand'] ?? $locales[$languageTag]['brand'] ?? null;

        if ($brand !== null) {
            $attributes['brand'] = [['value' => $brand]];
        }

        $manufacturer = $common['manufacturer'] ?? $locales[$languageTag]['manufacturer'] ?? null;

        if ($manufacturer !== null) {
            $attributes['manufacturer'] = [['value' => $manufacturer]];
        }

        if (isset($common['price'])) {
            $attributes['purchasable_offer'] = [[
                'our_price' => [[
                    'schedule' => [[
                        'value_with_tax' => (float) $common['price'],
                    ]],
                ]],
            ]];
        }

        return [
            'productType' => 'PRODUCT',
            'attributes'  => $attributes,
        ];
    }

    protected function normalizeAmazonProduct(array $product): array
    {
        $common = [];
        $attributes = $product['attributes'] ?? [];
        $summaries = $product['summaries'][0] ?? [];

        if (isset($attributes['item_name'][0]['value'])) {
            $common['item_name'] = $attributes['item_name'][0]['value'];
        } elseif (isset($summaries['itemName'])) {
            $common['item_name'] = $summaries['itemName'];
        }

        if (isset($attributes['product_description'][0]['value'])) {
            $common['product_description'] = $attributes['product_description'][0]['value'];
        }

        if (isset($attributes['bullet_point'][0]['value'])) {
            $common['bullet_point'] = $attributes['bullet_point'][0]['value'];
        }

        if (isset($attributes['brand'][0]['value'])) {
            $common['brand'] = $attributes['brand'][0]['value'];
        } elseif (isset($summaries['brand'])) {
            $common['brand'] = $summaries['brand'];
        }

        if (isset($attributes['manufacturer'][0]['value'])) {
            $common['manufacturer'] = $attributes['manufacturer'][0]['value'];
        } elseif (isset($summaries['manufacturer'])) {
            $common['manufacturer'] = $summaries['manufacturer'];
        }

        if (isset($product['asin'])) {
            $common['asin'] = $product['asin'];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }

    protected function getAccessToken(array $credentials, bool $forceRefresh = false): ?string
    {
        $clientId = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $refreshToken = $credentials['refresh_token'] ?? '';

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return null;
        }

        $cacheKey = 'amazon_sp_api_token_'.md5($clientId.$refreshToken);

        if (! $forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post('https://api.amazon.com/auth/o2/token', [
                    'grant_type'    => 'refresh_token',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->failed()) {
                Log::error('[Amazon] Failed to obtain access token', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;

            if ($accessToken) {
                Cache::put($cacheKey, $accessToken, 3500);
            }

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('[Amazon] Token refresh failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function getApiBase(string $region): string
    {
        $regionMap = [
            'us-east-1'      => 'https://sellingpartnerapi-na.amazon.com',
            'eu-west-1'      => 'https://sellingpartnerapi-eu.amazon.com',
            'us-west-2'      => 'https://sellingpartnerapi-fe.amazon.com',
            'ap-southeast-1' => 'https://sellingpartnerapi-fe.amazon.com',
        ];

        return $regionMap[$region] ?? 'https://sellingpartnerapi-na.amazon.com';
    }
}
