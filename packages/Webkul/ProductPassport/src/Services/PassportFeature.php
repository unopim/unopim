<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Contracts\Database\Query\Builder;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Services\ScopedConfig;

class PassportFeature
{
    private const string AUTO_PUBLISH = 'catalog.product_passport.settings.auto_publish';

    private const string ENABLED = 'catalog.product_passport.settings.enabled';

    public function __construct(private readonly ScopedConfig $config) {}

    /**
     * Whether the admin surface (menu item, grid, product panel, routes) is open
     * at all — true while the flag is on at the global scope or for a channel that
     * still exists, so a channel an operator deliberately kept on is not hidden by
     * the global switch. Rows orphaned by a deleted channel no longer count.
     *
     * This decides visibility only; every state-changing action stays gated per
     * channel by {@see self::enabledFor()}.
     */
    public function enabledAnywhere(): bool
    {
        return CoreConfig::query()
            ->where('code', self::ENABLED)
            ->whereNull('locale_code')
            ->where('value', '1')
            ->where(fn (Builder $query) => $query
                ->whereNull('channel_code')
                ->orWhereIn('channel_code', ChannelProxy::modelClass()::query()->select('code'))
            )
            ->exists();
    }

    #[\Deprecated(message: 'Use enabledAnywhere(); the flag was never global-scope only.')]
    public function globallyEnabled(): bool
    {
        return $this->enabledAnywhere();
    }

    public function enabledFor(Channel $channel): bool
    {
        return $this->valueFor(self::ENABLED, $channel);
    }

    public function autoPublishEnabledFor(Channel $channel): bool
    {
        return $this->enabledFor($channel)
            && $this->valueFor(self::AUTO_PUBLISH, $channel);
    }

    private function valueFor(string $code, Channel $channel): bool
    {
        return $this->config->enabled($code, $channel->code);
    }
}
