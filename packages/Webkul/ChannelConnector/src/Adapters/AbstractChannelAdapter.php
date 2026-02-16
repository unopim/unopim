<?php

namespace Webkul\ChannelConnector\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\ValueObjects\BatchSyncResult;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

abstract class AbstractChannelAdapter implements ChannelAdapterContract
{
    protected const RTL_LOCALES = [
        'ar_AE', 'ar_BH', 'ar_DZ', 'ar_EG', 'ar_IQ', 'ar_JO', 'ar_KW',
        'ar_LB', 'ar_LY', 'ar_MA', 'ar_OM', 'ar_QA', 'ar_SA', 'ar_SD',
        'ar_SY', 'ar_TN', 'ar_YE', 'he_IL', 'fa_IR', 'ur_PK',
    ];

    protected array $credentials = [];

    protected ?int $connectorId = null;

    public function setCredentials(array $credentials): static
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function setConnectorId(int $connectorId): static
    {
        $this->connectorId = $connectorId;

        return $this;
    }

    public function isRtlLocale(string $localeCode): bool
    {
        return in_array($localeCode, static::RTL_LOCALES);
    }

    public function syncProducts(Collection $products, array $localeMappedData): BatchSyncResult
    {
        $results = [];
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($products as $product) {
            $productData = $localeMappedData[$product->id] ?? null;

            if ($productData === null) {
                $skippedCount++;

                continue;
            }

            $this->throttle();

            $result = $this->syncProduct($product, $productData);
            $results[] = $result;

            if ($result->success) {
                $successCount++;
            } else {
                $failedCount++;
                $errors = array_merge($errors, $result->errors);
            }
        }

        return new BatchSyncResult(
            totalProcessed: count($results) + $skippedCount,
            successCount: $successCount,
            failedCount: $failedCount,
            skippedCount: $skippedCount,
            results: $results,
            errors: $errors,
        );
    }

    protected function throttle(?RateLimitConfig $config = null): void
    {
        $config = $config ?? $this->getRateLimitConfig();

        if (! $config->requestsPerSecond) {
            return;
        }

        $key = 'channel_connector_throttle_'.static::class;

        $executed = RateLimiter::attempt($key, $config->requestsPerSecond, fn () => true, 1);

        if (! $executed) {
            $seconds = RateLimiter::availableIn($key);
            Log::debug('[ChannelConnector] Rate limit throttle', ['adapter' => static::class, 'delay_seconds' => $seconds]);
            usleep($seconds * 1_000_000);
        }
    }

    abstract public function testConnection(array $credentials): ConnectionResult;

    abstract public function syncProduct(Product $product, array $localeMappedData): SyncResult;

    abstract public function fetchProduct(string $externalId, ?string $locale = null): ?array;

    abstract public function deleteProduct(string $externalId): bool;

    abstract public function getChannelFields(?string $locale = null): array;

    abstract public function getSupportedLocales(): array;

    abstract public function getSupportedCurrencies(): array;

    abstract public function registerWebhooks(array $events, string $callbackUrl): bool;

    abstract public function verifyWebhook(Request $request): bool;

    abstract public function refreshCredentials(): ?array;

    abstract public function getRateLimitConfig(): RateLimitConfig;
}
