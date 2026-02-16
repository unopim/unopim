<?php

namespace Webkul\Shopify\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;
use Webkul\Shopify\Http\Client\GraphQLApiClient;

class ShopifyAdapter extends AbstractChannelAdapter
{
    protected const SHOPIFY_EVENT_MAP = [
        'product.created' => 'PRODUCTS_CREATE',
        'product.updated' => 'PRODUCTS_UPDATE',
        'product.deleted' => 'PRODUCTS_DELETE',
    ];

    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $shopUrl = $credentials['shop_url'] ?? '';
            $accessToken = $credentials['access_token'] ?? '';

            if (empty($shopUrl) || empty($accessToken)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Shop URL and access token are required.',
                    errors: ['Missing required credentials'],
                );
            }

            $response = $this->graphqlRequest($shopUrl, $accessToken, '{
                shop {
                    name
                    primaryDomain { url }
                    productCount: productsCount { count }
                }
            }');

            if (isset($response['errors'])) {
                return new ConnectionResult(
                    success: false,
                    message: $response['errors'][0]['message'] ?? 'GraphQL error',
                    errors: array_column($response['errors'] ?? [], 'message'),
                );
            }

            $shop = $response['data']['shop'] ?? [];

            return new ConnectionResult(
                success: true,
                message: 'Connection verified successfully.',
                channelInfo: [
                    'store_name'    => $shop['name'] ?? '',
                    'store_url'     => $shop['primaryDomain']['url'] ?? '',
                    'product_count' => $shop['productCount']['count'] ?? 0,
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
            $client = $this->getClient();

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'shopify'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;

            if ($existingExternalId) {
                return $this->updateShopifyProduct($client, $existingExternalId, $localeMappedData);
            }

            return $this->createShopifyProduct($client, $localeMappedData);
        } catch (\Exception $e) {
            Log::error('[Shopify] syncProduct failed', [
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
            $client = $this->getClient();
            $response = $client->request('getProductById', ['id' => $externalId]);

            $productData = $response['data']['product'] ?? null;

            if (! $productData) {
                return null;
            }

            Log::info('[Shopify] Product fetched', ['external_id' => $externalId]);

            return $this->normalizeShopifyProduct($productData);
        } catch (\Exception $e) {
            Log::warning('[Shopify] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $client = $this->getClient();
            $response = $client->request('productDelete', [
                'input' => ['id' => $externalId],
            ]);

            $userErrors = $response['data']['productDelete']['userErrors'] ?? [];

            if (! empty($userErrors)) {
                Log::warning('[Shopify] deleteProduct userErrors', [
                    'external_id' => $externalId,
                    'errors'      => $userErrors,
                ]);

                return false;
            }

            Log::info('[Shopify] Product deleted', ['external_id' => $externalId]);

            return true;
        } catch (\Exception $e) {
            Log::error('[Shopify] deleteProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannelFields(?string $locale = null): array
    {
        return [
            ['code' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'is_translatable' => true],
            ['code' => 'descriptionHtml', 'label' => 'Description', 'type' => 'text', 'required' => false, 'is_translatable' => true],
            ['code' => 'vendor', 'label' => 'Vendor', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'productType', 'label' => 'Product Type', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'tags', 'label' => 'Tags', 'type' => 'array', 'required' => false, 'is_translatable' => false],
            ['code' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'is_translatable' => false],
            ['code' => 'price', 'label' => 'Price', 'type' => 'price', 'required' => true, 'is_translatable' => false],
            ['code' => 'compareAtPrice', 'label' => 'Compare At Price', 'type' => 'price', 'required' => false, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'barcode', 'label' => 'Barcode', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'weight', 'label' => 'Weight', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'images', 'label' => 'Images', 'type' => 'media', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        try {
            $shopUrl = $this->credentials['shop_url'] ?? '';
            $accessToken = $this->credentials['access_token'] ?? '';

            $response = $this->graphqlRequest($shopUrl, $accessToken, '{
                shopLocales { locale primary published }
            }');

            return array_map(
                fn ($l) => $l['locale'],
                $response['data']['shopLocales'] ?? []
            );
        } catch (\Exception) {
            return ['en'];
        }
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $client = $this->getClient();
            $allSuccess = true;

            foreach ($events as $event) {
                $shopifyTopic = self::SHOPIFY_EVENT_MAP[$event] ?? null;

                if (! $shopifyTopic) {
                    Log::warning('[Shopify] Unknown webhook event, skipping', ['event' => $event]);

                    continue;
                }

                $response = $client->request('webhookSubscriptionCreate', [
                    'topic'               => $shopifyTopic,
                    'webhookSubscription' => [
                        'callbackUrl' => $callbackUrl,
                        'format'      => 'JSON',
                    ],
                ]);

                $userErrors = $response['data']['webhookSubscriptionCreate']['userErrors'] ?? [];

                if (! empty($userErrors)) {
                    Log::warning('[Shopify] Webhook registration failed', [
                        'topic'  => $shopifyTopic,
                        'errors' => $userErrors,
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[Shopify] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $secret = $this->credentials['webhook_secret'] ?? '';

        if (empty($hmac) || empty($secret)) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $data, $secret, true));

        return hash_equals($calculated, $hmac);
    }

    public function refreshCredentials(): ?array
    {
        return null; // Shopify uses permanent access tokens
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(
            requestsPerSecond: 2,
            costPerQuery: 1,
            costPerMutation: 10,
            maxCostPerSecond: 50,
        );
    }

    protected function getClient(): GraphQLApiClient
    {
        $shopUrl = $this->credentials['shop_url'] ?? '';
        $accessToken = $this->credentials['access_token'] ?? '';
        $apiVersion = $this->credentials['api_version'] ?? '2024-01';

        if (empty($shopUrl) || empty($accessToken)) {
            throw new \RuntimeException('Shopify credentials (shop_url, access_token) are required.');
        }

        // Ensure shopUrl has protocol for GraphQLApiClient
        if (! str_starts_with($shopUrl, 'http')) {
            $shopUrl = 'https://'.$shopUrl;
        }

        return new GraphQLApiClient($shopUrl, $accessToken, $apiVersion);
    }

    protected function createShopifyProduct(GraphQLApiClient $client, array $localeMappedData): SyncResult
    {
        $productInput = $this->buildProductInput($localeMappedData);

        $response = $client->request('createProduct', [
            'product' => $productInput,
        ]);

        $userErrors = $response['data']['productCreate']['userErrors'] ?? [];

        if (! empty($userErrors)) {
            return new SyncResult(
                success: false,
                action: 'failed',
                errors: array_map(fn ($e) => $e['message'] ?? 'Unknown error', $userErrors),
            );
        }

        $shopifyProductId = $response['data']['productCreate']['product']['id'] ?? null;

        if (! $shopifyProductId) {
            return new SyncResult(
                success: false,
                action: 'failed',
                errors: ['No product ID returned from Shopify'],
            );
        }

        // Handle translations for non-default locales
        $this->syncTranslations($client, $shopifyProductId, $localeMappedData);

        Log::info('[Shopify] Product created', ['external_id' => $shopifyProductId]);

        return new SyncResult(
            success: true,
            externalId: $shopifyProductId,
            action: 'created',
        );
    }

    protected function updateShopifyProduct(GraphQLApiClient $client, string $externalId, array $localeMappedData): SyncResult
    {
        $productInput = $this->buildProductInput($localeMappedData);
        $productInput['id'] = $externalId;

        $response = $client->request('productUpdate', [
            'product' => $productInput,
        ]);

        $userErrors = $response['data']['productUpdate']['userErrors'] ?? [];

        if (! empty($userErrors)) {
            return new SyncResult(
                success: false,
                externalId: $externalId,
                action: 'failed',
                errors: array_map(fn ($e) => $e['message'] ?? 'Unknown error', $userErrors),
            );
        }

        // Handle translations for non-default locales
        $this->syncTranslations($client, $externalId, $localeMappedData);

        Log::info('[Shopify] Product updated', ['external_id' => $externalId]);

        return new SyncResult(
            success: true,
            externalId: $externalId,
            action: 'updated',
        );
    }

    protected function buildProductInput(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        // Use the first available locale for default values if common is sparse
        $defaultLocaleData = ! empty($locales) ? reset($locales) : [];

        $input = [];

        // Title: prefer common, fallback to first locale
        $title = $common['title'] ?? $defaultLocaleData['title'] ?? null;
        if ($title !== null) {
            $input['title'] = $title;
        }

        // Description
        $description = $common['descriptionHtml'] ?? $defaultLocaleData['descriptionHtml'] ?? null;
        if ($description !== null) {
            $input['descriptionHtml'] = $description;
        }

        // Simple string fields
        foreach (['vendor', 'productType'] as $field) {
            if (isset($common[$field])) {
                $input[$field] = $common[$field];
            }
        }

        // Tags (array to comma-separated string)
        if (isset($common['tags'])) {
            $input['tags'] = is_array($common['tags']) ? $common['tags'] : [$common['tags']];
        }

        // Status mapping: active/draft/archived → ACTIVE/DRAFT/ARCHIVED
        if (isset($common['status'])) {
            $statusMap = [
                'active'   => 'ACTIVE',
                'draft'    => 'DRAFT',
                'archived' => 'ARCHIVED',
                'ACTIVE'   => 'ACTIVE',
                'DRAFT'    => 'DRAFT',
                'ARCHIVED' => 'ARCHIVED',
            ];
            $input['status'] = $statusMap[$common['status']] ?? 'DRAFT';
        }

        // Variant-level fields (price, sku, barcode, weight) go in the default variant
        $variantFields = [];

        if (isset($common['price'])) {
            $variantFields['price'] = (string) $common['price'];
        }

        if (isset($common['compareAtPrice'])) {
            $variantFields['compareAtPrice'] = (string) $common['compareAtPrice'];
        }

        if (isset($common['sku'])) {
            $variantFields['sku'] = $common['sku'];
        }

        if (isset($common['barcode'])) {
            $variantFields['barcode'] = $common['barcode'];
        }

        return $input;
    }

    protected function syncTranslations(GraphQLApiClient $client, string $shopifyProductId, array $localeMappedData): void
    {
        $locales = $localeMappedData['locales'] ?? [];

        if (empty($locales)) {
            return;
        }

        // The translatable Shopify fields and their translation keys
        $translatableFieldKeys = [
            'title'           => 'title',
            'descriptionHtml' => 'body_html',
        ];

        // Skip the first locale (assumed to be the default/primary)
        $localeKeys = array_keys($locales);

        foreach ($localeKeys as $localeCode) {
            $localeData = $locales[$localeCode];
            $translations = [];

            foreach ($translatableFieldKeys as $fieldCode => $shopifyKey) {
                if (isset($localeData[$fieldCode])) {
                    $translations[] = [
                        'locale'                    => $localeCode,
                        'key'                       => $shopifyKey,
                        'value'                     => $localeData[$fieldCode],
                        'translatableContentDigest' => md5($localeData[$fieldCode]),
                    ];
                }
            }

            if (! empty($translations)) {
                try {
                    $client->request('createTranslation', [
                        'id'           => $shopifyProductId,
                        'translations' => $translations,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[Shopify] Translation sync failed', [
                        'product_id' => $shopifyProductId,
                        'locale'     => $localeCode,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    protected function normalizeShopifyProduct(array $productData): array
    {
        $common = [];
        $locales = [];

        // Product-level fields
        if (isset($productData['title'])) {
            $common['title'] = $productData['title'];
        }

        if (isset($productData['descriptionHtml'])) {
            $common['descriptionHtml'] = $productData['descriptionHtml'];
        }

        if (isset($productData['vendor'])) {
            $common['vendor'] = $productData['vendor'];
        }

        if (isset($productData['productType'])) {
            $common['productType'] = $productData['productType'];
        }

        if (isset($productData['tags'])) {
            $common['tags'] = $productData['tags'];
        }

        if (isset($productData['status'])) {
            $common['status'] = strtolower($productData['status']);
        }

        // Variant-level fields from the first variant
        $firstVariant = $productData['variants']['edges'][0]['node'] ?? null;

        if ($firstVariant) {
            if (isset($firstVariant['price'])) {
                $common['price'] = $firstVariant['price'];
            }

            if (isset($firstVariant['compareAtPrice'])) {
                $common['compareAtPrice'] = $firstVariant['compareAtPrice'];
            }

            if (isset($firstVariant['sku'])) {
                $common['sku'] = $firstVariant['sku'];
            }

            if (isset($firstVariant['barcode'])) {
                $common['barcode'] = $firstVariant['barcode'];
            }

            $weight = $firstVariant['inventoryItem']['measurement']['weight']['value'] ?? null;
            if ($weight !== null) {
                $common['weight'] = $weight;
            }
        }

        return [
            'common'  => $common,
            'locales' => $locales,
        ];
    }

    protected function graphqlRequest(string $shopUrl, string $accessToken, string $query): array
    {
        // Validate shop URL to prevent SSRF attacks
        if (! filter_var($shopUrl, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException('Invalid shop URL provided.');
        }

        // Ensure shopUrl doesn't contain protocol or path
        $shopUrl = preg_replace('/^https?:\/\//', '', $shopUrl);
        $shopUrl = strtr($shopUrl, '/');

        $url = "https://{$shopUrl}/admin/api/2024-01/graphql.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "X-Shopify-Access-Token: {$accessToken}",
            ],
            CURLOPT_POSTFIELDS => json_encode(['query' => $query]),
            CURLOPT_TIMEOUT    => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            throw new \RuntimeException("Shopify API request failed with HTTP {$httpCode}");
        }

        return json_decode($response, true) ?? [];
    }
}
