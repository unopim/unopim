<?php

namespace Webkul\Salla\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Adapters\AbstractChannelAdapter;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;
use Webkul\Salla\Models\SallaProductMapping;

class SallaAdapter extends AbstractChannelAdapter
{
    protected const API_BASE = 'https://api.salla.dev/admin/v2';

    public function testConnection(array $credentials): ConnectionResult
    {
        try {
            $accessToken = $credentials['access_token'] ?? '';

            if (empty($accessToken)) {
                return new ConnectionResult(
                    success: false,
                    message: 'Access token is required.',
                    errors: ['Missing access token'],
                );
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get(self::API_BASE.'/products', ['per_page' => 1]);

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
                    'store_name'    => $data['data'][0]['store']['name'] ?? 'Salla Store',
                    'product_count' => $data['pagination']['total'] ?? 0,
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
            $this->ensureValidToken();

            $accessToken = $this->credentials['access_token'] ?? '';

            // Use adapter-specific product mapping table
            $existingMapping = SallaProductMapping::where('product_id', $product->id)
                ->where('connector_id', $this->connectorId)
                ->first();

            $existingExternalId = $existingMapping?->external_id;
            $body = $this->buildSallaProductBody($localeMappedData);

            if ($existingExternalId) {
                $response = Http::withToken($accessToken)
                    ->timeout(30)
                    ->put(self::API_BASE.'/products/'.$existingExternalId, $body);
            } else {
                $response = Http::withToken($accessToken)
                    ->timeout(30)
                    ->post(self::API_BASE.'/products', $body);
            }

            if ($response->failed()) {
                $errorBody = $response->json();

                return new SyncResult(
                    success: false,
                    externalId: $existingExternalId,
                    action: 'failed',
                    errors: [$errorBody['error']['message'] ?? 'HTTP '.$response->status()],
                );
            }

            $data = $response->json();
            $sallaProductId = (string) ($data['data']['id'] ?? $existingExternalId ?? '');

            // Update or create adapter-specific mapping
            SallaProductMapping::updateOrCreate(
                [
                    'product_id'   => $product->id,
                    'connector_id' => $this->connectorId,
                ],
                [
                    'external_id'    => $sallaProductId,
                    'external_sku'   => $localeMappedData['sku'] ?? null,
                    'variant_data'   => $localeMappedData['variants'] ?? [],
                    'sync_status'    => 'synced',
                    'last_synced_at' => now(),
                    'error_message'  => null,
                ]
            );

            Log::info('[Salla] Product synced', [
                'product_id'  => $product->id,
                'external_id' => $sallaProductId,
                'action'      => $existingExternalId ? 'updated' : 'created',
            ]);

            return new SyncResult(
                success: true,
                externalId: $sallaProductId,
                action: $existingExternalId ? 'updated' : 'created',
            );
        } catch (\Exception $e) {
            Log::error('[Salla] syncProduct failed', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            // Update mapping with error
            SallaProductMapping::updateOrCreate(
                [
                    'product_id'   => $product->id,
                    'connector_id' => $this->connectorId,
                ],
                [
                    'sync_status'   => 'failed',
                    'error_message' => $e->getMessage(),
                ]
            );

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
            $this->ensureValidToken();

            $response = Http::withToken($this->credentials['access_token'] ?? '')
                ->timeout(30)
                ->get(self::API_BASE.'/products/'.$externalId);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $product = $data['data'] ?? null;

            if (! $product) {
                return null;
            }

            Log::info('[Salla] Product fetched', ['external_id' => $externalId]);

            return $this->normalizeSallaProduct($product);
        } catch (\Exception $e) {
            Log::warning('[Salla] fetchProduct failed', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteProduct(string $externalId): bool
    {
        try {
            $this->ensureValidToken();

            $response = Http::withToken($this->credentials['access_token'] ?? '')
                ->timeout(30)
                ->delete(self::API_BASE.'/products/'.$externalId);

            if ($response->successful()) {
                Log::info('[Salla] Product deleted', ['external_id' => $externalId]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Salla] deleteProduct failed', [
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
            ['code' => 'sale_price', 'label' => 'Sale Price', 'type' => 'price', 'required' => false, 'is_translatable' => false],
            ['code' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => false, 'is_translatable' => false],
            ['code' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'weight', 'label' => 'Weight', 'type' => 'number', 'required' => false, 'is_translatable' => false],
            ['code' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => false, 'is_translatable' => false],
            ['code' => 'images', 'label' => 'Images', 'type' => 'media', 'required' => false, 'is_translatable' => false],
        ];
    }

    public function getSupportedLocales(): array
    {
        return ['ar', 'en'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['SAR', 'USD', 'EUR', 'KWD', 'BHD', 'AED'];
    }

    public function registerWebhooks(array $events, string $callbackUrl): bool
    {
        try {
            $this->ensureValidToken();

            $accessToken = $this->credentials['access_token'] ?? '';
            $allSuccess = true;

            foreach ($events as $event) {
                $response = Http::withToken($accessToken)
                    ->timeout(30)
                    ->post(self::API_BASE.'/webhooks', [
                        'event' => $event,
                        'url'   => $callbackUrl,
                    ]);

                if ($response->failed()) {
                    Log::warning('[Salla] Webhook registration failed', [
                        'event'  => $event,
                        'status' => $response->status(),
                    ]);
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error('[Salla] registerWebhooks failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Salla-Signature');
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
        $refreshToken = $this->credentials['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://accounts.salla.sa/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $this->credentials['client_id'] ?? '',
                'client_secret' => $this->credentials['client_secret'] ?? '',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                    'expires_at'    => now()->addSeconds($data['expires_in'] ?? 3600)->toIso8601String(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('[Salla] Token refresh failed', [
                'error'     => $e->getMessage(),
                'client_id' => $this->credentials['client_id'] ?? 'unknown',
            ]);
        }

        return null;
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(
            requestsPerSecond: 10,
        );
    }

    protected function ensureValidToken(): void
    {
        $expiresAt = $this->credentials['expires_at'] ?? null;

        if ($expiresAt && now()->greaterThan($expiresAt)) {
            $refreshed = $this->refreshCredentials();

            if ($refreshed) {
                $this->credentials = array_merge($this->credentials, $refreshed);
            }
        }
    }

    protected function buildSallaProductBody(array $localeMappedData): array
    {
        $common = $localeMappedData['common'] ?? [];
        $locales = $localeMappedData['locales'] ?? [];

        $body = [];

        // Find first matching locale by prefix (supports ar_AE, en_US, etc.)
        $arData = collect($locales)->first(fn ($v, $k) => str_starts_with($k, 'ar')) ?? [];
        $enData = collect($locales)->first(fn ($v, $k) => str_starts_with($k, 'en')) ?? [];

        // Name: prefer Arabic locale, fallback to common, fallback to English
        $name = $arData['name'] ?? $common['name'] ?? $enData['name'] ?? null;
        if ($name !== null) {
            $body['name'] = $name;
        }

        // Description
        $description = $arData['description'] ?? $common['description'] ?? $enData['description'] ?? null;
        if ($description !== null) {
            $body['description'] = $description;
        }

        // Price
        if (isset($common['price'])) {
            $body['price'] = [
                'amount'   => (float) $common['price'],
                'currency' => $this->credentials['currency'] ?? 'SAR',
            ];
        }

        // Sale price
        if (isset($common['sale_price'])) {
            $body['sale_price'] = [
                'amount'   => (float) $common['sale_price'],
                'currency' => $this->credentials['currency'] ?? 'SAR',
            ];
        }

        // Simple fields
        if (isset($common['sku'])) {
            $body['sku'] = $common['sku'];
        }

        if (isset($common['quantity'])) {
            $body['quantity'] = (int) $common['quantity'];
        }

        if (isset($common['weight'])) {
            $body['weight'] = (float) $common['weight'];
        }

        // Status mapping
        if (isset($common['status'])) {
            $statusMap = [
                'active'   => 'sale',
                'draft'    => 'hidden',
                'archived' => 'out',
                'sale'     => 'sale',
                'hidden'   => 'hidden',
                'out'      => 'out',
            ];
            $body['status'] = $statusMap[$common['status']] ?? 'hidden';
        }

        return $body;
    }

    protected function normalizeSallaProduct(array $product): array
    {
        $common = [];

        if (isset($product['name'])) {
            $common['name'] = $product['name'];
        }

        if (isset($product['description'])) {
            $common['description'] = $product['description'];
        }

        if (isset($product['price']['amount'])) {
            $common['price'] = $product['price']['amount'];
        }

        if (isset($product['sale_price']['amount'])) {
            $common['sale_price'] = $product['sale_price']['amount'];
        }

        if (isset($product['sku'])) {
            $common['sku'] = $product['sku'];
        }

        if (isset($product['quantity'])) {
            $common['quantity'] = $product['quantity'];
        }

        if (isset($product['weight'])) {
            $common['weight'] = $product['weight'];
        }

        if (isset($product['status'])) {
            $common['status'] = $product['status'];
        }

        return [
            'common'  => $common,
            'locales' => [],
        ];
    }
}
