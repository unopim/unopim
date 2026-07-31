<?php

namespace Webkul\Core\Services;

use Webkul\Core\Models\CoreConfigProxy;

/**
 * Reads a `core_config` value at an explicit scope.
 *
 * `core()->getConfigData()` resolves the field declaration first and, for a field
 * that is not declared channel-based, matches on the code alone — so an install
 * carrying rows for the same code at several scopes (seeders and the settings
 * screen both write them) answers with whichever row the database returns first.
 * A kill switch cannot be read that way: it must answer for the channel asked
 * about, fall back to the global row, and let the newest row win a duplicate.
 */
class ScopedConfig
{
    public function value(string $code, ?string $channelCode = null): ?string
    {
        if ($channelCode !== null && $channelCode !== '') {
            $channelValue = $this->latestValue($code, $channelCode);

            if ($channelValue !== null) {
                return $channelValue;
            }
        }

        return $this->latestValue($code, null);
    }

    public function enabled(string $code, ?string $channelCode = null): bool
    {
        return (bool) $this->value($code, $channelCode);
    }

    private function latestValue(string $code, ?string $channelCode): ?string
    {
        $value = CoreConfigProxy::modelClass()::query()
            ->where('code', $code)
            ->when(
                $channelCode === null,
                fn ($query) => $query->whereNull('channel_code'),
                fn ($query) => $query->where('channel_code', $channelCode),
            )
            ->whereNull('locale_code')
            ->latest('id')
            ->value('value');

        return $value === null ? null : (string) $value;
    }
}
