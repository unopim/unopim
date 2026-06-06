<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VersionCheck
{
    /**
     * Returns the latest released stable version of UnoPim, or null if it
     * cannot be determined. Cached to avoid hitting the network on every page.
     */
    public function latestVersion(): ?string
    {
        return Cache::remember(
            'unopim_latest_version',
            now()->addHours(config('help.version_check.cache_hours', 12)),
            fn () => $this->fetchLatest()
        );
    }

    /**
     * Whether the running version is older than the latest released version.
     */
    public function isOutdated(): bool
    {
        $latest = $this->latestVersion();

        return $latest !== null && version_compare($this->currentVersion(), $latest, '<');
    }

    /**
     * The currently running UnoPim version.
     */
    public function currentVersion(): string
    {
        return core()->version();
    }

    /**
     * Fetch the latest stable version from Packagist.
     * Fail-silent: returns the configured fallback (may be null) on any error.
     */
    protected function fetchLatest(): ?string
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(3)
                ->get(config('help.version_check.packagist'));

            if ($response->failed()) {
                return config('help.version_check.fallback_latest');
            }

            $data = $response->json();

            if (! is_array($data)) {
                return config('help.version_check.fallback_latest');
            }

            $versions = $data['packages']['unopim/unopim'] ?? [];

            $latest = null;

            foreach ($versions as $package) {
                $version = $package['version'] ?? null;

                if (! $version) {
                    continue;
                }

                $normalized = ltrim($version, 'vV');

                if (! $this->isStable($normalized)) {
                    continue;
                }

                if ($latest === null || version_compare($normalized, $latest, '>')) {
                    $latest = $normalized;
                }
            }

            return $latest ?? config('help.version_check.fallback_latest');
        } catch (\Throwable $e) {
            return config('help.version_check.fallback_latest');
        }
    }

    /**
     * Whether the given version string represents a stable release
     * (no dev / beta / RC / alpha pre-release suffixes).
     */
    protected function isStable(string $version): bool
    {
        return ! preg_match('/(dev|alpha|beta|rc)/i', $version);
    }
}
