<?php

namespace Webkul\ProductPassport\Services;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;

class PassportFeature
{
    private const string AUTO_PUBLISH = 'catalog.product_passport.settings.auto_publish';

    private const string ENABLED = 'catalog.product_passport.settings.enabled';

    public function globallyEnabled(): bool
    {
        return CoreConfig::query()
            ->where('code', self::ENABLED)
            ->where('value', '1')
            ->exists();
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
        $channelValue = CoreConfig::query()
            ->where('code', $code)
            ->where('channel_code', $channel->code)
            ->whereNull('locale_code')
            ->latest('id')
            ->value('value');

        if ($channelValue !== null) {
            return (bool) $channelValue;
        }

        return (bool) CoreConfig::query()
            ->where('code', $code)
            ->whereNull('channel_code')
            ->whereNull('locale_code')
            ->latest('id')
            ->value('value');
    }
}
