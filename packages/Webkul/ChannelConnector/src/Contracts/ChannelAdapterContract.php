<?php

namespace Webkul\ChannelConnector\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\ChannelConnector\ValueObjects\BatchSyncResult;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;
use Webkul\ChannelConnector\ValueObjects\RateLimitConfig;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

interface ChannelAdapterContract
{
    public function testConnection(array $credentials): ConnectionResult;

    public function syncProduct(Product $product, array $localeMappedData): SyncResult;

    public function syncProducts(Collection $products, array $localeMappedData): BatchSyncResult;

    public function fetchProduct(string $externalId, ?string $locale = null): ?array;

    public function deleteProduct(string $externalId): bool;

    public function getChannelFields(?string $locale = null): array;

    public function getSupportedLocales(): array;

    public function getSupportedCurrencies(): array;

    public function isRtlLocale(string $localeCode): bool;

    public function registerWebhooks(array $events, string $callbackUrl): bool;

    public function verifyWebhook(Request $request): bool;

    public function refreshCredentials(): ?array;

    public function setCredentials(array $credentials): static;

    public function getRateLimitConfig(): RateLimitConfig;
}
