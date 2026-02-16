<?php

namespace Webkul\Magento2\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

class Magento2Adapter extends AbstractChannelAdapter
{
    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $storeUrl = $credentials['store_url'] ?? '';
            $accessToken = $credentials['access_token'] ?? '';

            if (empty($storeUrl)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Store URL is required.',
                    errors: ['Missing store URL'],
                );
            }

            if (empty($accessToken)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Access token is required.',
                    errors: ['Missing access token'],
                );
            }

            $baseUrl = rtrim($storeUrl, '/').'/rest/V1';

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($baseUrl.'/store/storeConfigs');

            if ($response->failed()) {
                return new ConnectionResult(
                    success: false,
                    message: 'Connection failed: HTTP '.$response->status(),
                    errors: [$response->body()],
                );
            }

            $data = $response->json();
            $storeName = $data[0]['base_currency_code'] ?? 'Magento 2 Store';

            return new ConnectionResult(
                success: true,
                message: 'Connection verified successfully.',
                channelInfo: [
                    'store_name'    => $storeName,
                    'store_configs' => count($data),
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
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($storeUrl) || empty($accessToken)) {
                return new SyncResult(
                    success: false,
                    action: 'failed',
                    errors: ['Magento 2 API credentials (store_url, access_token) are required'],
                );
            }

            $existingMapping = ProductChannelMapping::where('product_id', $product->id)
                ->whereHas('connector', fn ($q) => $q->where('channel_type', 'magento2'))
                ->first();

            $existingExternalId = $existingMapping->external_id ?? null;
            $body = $this->buildMagentoProductBody($localeMappedData, $product);
            $baseUrl = rtrim($storeUrl, '/').'/rest/V1';

            if ($existingExternalId) {
                $response = Http::withToken($accessToken)
                    ->timeout(30)
                    ->put($baseUrl.'/products/'.$existingExternalId, $body);
            } else {
                $response = Http::withToken($accessToken)
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
            // Magento identifies products by SKU
            $externalId = (string) ($data['sku'] ?? $product->sku ?? $existingExternalId ?? '');

            return new SyncResult(
                success: true,
                externalId: $externalId,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[Magento2] syncProduct failed', [
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
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($storeUrl) || empty($accessToken)) {
                return null;
            }

            $baseUrl = rtrim($storeUrl, '/').'/rest/V1';

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($baseUrl.'/products/'.$externalId);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return $this->normalizeM2Product($data);
        } catch (\Exception $e) {
            Log::warning('[Magento2] fetchProduct failed', [
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
            $accessToken = $this->credentials['access_token'] ?? '';

            if (empty($storeUrl) || empty($accessToken)) {
                return false;
            }

            $baseUrl = rtrim($storeUrl, '/').'/rest/V1';

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->delete($baseUrl.'/products/'.$externalId);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Magento2] deleteProduct failed', [
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
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => true, 'is_translatable' => false],
            ['code' => 'status', 'label' => 'Status', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'visibility', 'label' => 'Visibility', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'weight', 'label' => 'Weight', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'type_id', 'label' => 'Product Type', 'type' => 'string', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['en_US', 'fr_FR', 'de_DE', 'es_ES', 'ar_SA'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'SAR'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        // Magento 2 does not natively support webhook registration via REST API.
        // Webhooks are typically configured via Admin Panel or custom modules.
        return true;
    }

    public function verifyWebhook(Request $request): bool
    {
        // Magento 2 does not have a native webhook signature mechanism.
        // Verify using a shared secret token in the request header.
        $token = $request->header('X-Magento-Webhook-Token');
        $secret = $this->credentials['webhook_secret'] ?? '';

        if (empty($token) || empty($secret)) {
            return false;
        }

        return hash_equals($secret, $token);
    }

    public function refreshCredentials(): ?array
    {
        return null;
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(requestsPerSecond: 4);
    }

    protected function buildMagentoProductBody(array $localeMappedData, Product $product): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $magentoProduct = [];

        // Name: prefer en_US locale, fallback to common
        $name = $locales['en_US']['name'] ?? $locales['en']['name'] ?? $common['name'] ?? null;
        if ($name !== null) {
            $magentoProduct['name'] = $name;
        }

        // SKU
        $sku = $common['sku'] ?? $product->sku ?? null;
        if ($sku !== null) {
            $magentoProduct['sku'] = $sku;
        }

        // Price
        if (isset($common['price'])) {
            $magentoProduct['price'] = (float) $common['price'];
        }

        // Weight
        if (isset($common['weight'])) {
            $magentoProduct['weight'] = (float) $common['weight'];
        }

        // Status mapping: active->1, draft->2, archived->2
        if (isset($common['status'])) {
            $statusMap = [
                'active'   => 1,
                'draft'    => 2,
                'archived' => 2,
            ];
            $magentoProduct['status'] = $statusMap[$common['status']] ?? 2;
        }

        // Visibility
        if (isset($common['visibility'])) {
            $magentoProduct['visibility'] = (int) $common['visibility'];
        } else {
            $magentoProduct['visibility'] = 4; // Default: catalog and search
        }

        // Type ID
        $magentoProduct['type_id'] = $common['type_id'] ?? 'simple';

        // Attribute set ID (default)
        $magentoProduct['attribute_set_id'] = (int) ($common['attribute_set_id'] ?? 4);

        // Custom attributes (description goes here)
        $customAttributes = [];

        $description = $locales['en_US']['description'] ?? $locales['en']['description'] ?? $common['description'] ?? null;
        if ($description !== null) {
            $customAttributes[] = [
                'attribute_code' => 'description',
                'value'          => $description,
            ];
        }

        if (! empty($customAttributes)) {
            $magentoProduct['custom_attributes'] = $customAttributes;
        }

        return ['product' => $magentoProduct];
    }

    protected function normalizeM2Product(array $product): array
    {
        $common = [];

        if (isset($product['name'])) {
            $common['name'] = $product['name'];
        }

        if (isset($product['sku'])) {
            $common['sku'] = $product['sku'];
        }

        if (isset($product['price'])) {
            $common['price'] = $product['price'];
        }

        if (isset($product['weight'])) {
            $common['weight'] = $product['weight'];
        }

        // Status mapping: 1->active, 2->draft
        if (isset($product['status'])) {
            $statusMap = [
                1 => 'active',
                2 => 'draft',
            ];
            $common['status'] = $statusMap[$product['status']] ?? 'draft';
        }

        if (isset($product['visibility'])) {
            $common['visibility'] = $product['visibility'];
        }

        if (isset($product['type_id'])) {
            $common['type_id'] = $product['type_id'];
        }

        // Extract description from custom_attributes
        if (isset($product['custom_attributes']) && is_array($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attribute) {
                if (($attribute['attribute_code'] ?? '') === 'description') {
                    $common['description'] = $attribute['value'] ?? '';

                    break;
                }
            }
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }
}
