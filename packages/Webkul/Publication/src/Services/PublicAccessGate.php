<?php

namespace Webkul\Publication\Services;

use Webkul\Core\Services\ScopedConfig;

/**
 * Whether the public tier would actually serve what is published.
 *
 * Publishing and serving are separate switches: a passport can be published
 * while the env kill switch (`publication.enabled`) or the channel's
 * `general.publication.settings.enabled` leaves every public URL answering 404.
 * Publishers consult this so a dead version is refused rather than minted.
 */
class PublicAccessGate
{
    public const string ENABLED = 'general.publication.settings.enabled';

    public function __construct(private readonly ScopedConfig $config) {}

    public function globallyEnabled(): bool
    {
        return (bool) config('publication.enabled');
    }

    /**
     * Read through {@see ScopedConfig} rather than `core()->getConfigData()`: the
     * field is not declared channel-based, so the generic reader matches on the
     * code alone and a stale duplicate row can keep the tier alive after the
     * settings screen switched it off.
     */
    public function enabledForChannel(?string $channelCode): bool
    {
        if ($channelCode === null || $channelCode === '') {
            return false;
        }

        return $this->globallyEnabled()
            && $this->config->enabled(self::ENABLED, $channelCode);
    }

    /**
     * @param  iterable<int, string|null>  $channelCodes
     * @return array<string, bool> keyed by channel code, so a bulk action resolves each code once
     */
    public function enabledByChannel(iterable $channelCodes): array
    {
        return collect($channelCodes)
            ->filter()
            ->unique()
            ->mapWithKeys(fn (string $code): array => [$code => $this->enabledForChannel($code)])
            ->all();
    }
}
