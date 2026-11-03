<?php

namespace Webkul\Product\Services;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * Resolves the channel/locale scope the completeness property is read in.
 */
class ChannelCompletenessColumn
{
    /**
     * Requested channel code the memoized scope below was resolved for.
     * `false` means nothing has been resolved yet, since `null` is itself a
     * valid outcome (no channel requested).
     */
    protected string|false|null $resolvedFor = false;

    protected ?array $scope = null;

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
    ) {}

    /**
     * @return array{channel: Channel, locale: Locale}|null
     */
    public function resolve(): ?array
    {
        $channelCode = core()->getRequestedChannelCode(false);

        if ($this->resolvedFor === $channelCode) {
            return $this->scope;
        }

        $this->resolvedFor = $channelCode;

        return $this->scope = $this->resolveScope($channelCode);
    }

    /**
     * Elasticsearch indexes the cross-channel average only, so per-channel
     * scoping stays off while it is enabled and both the displayed column and
     * the filtered rows keep reading that average.
     */
    protected function resolveScope(?string $channelCode): ?array
    {
        if (! $channelCode || config('elasticsearch.enabled')) {
            return null;
        }

        $channel = $this->channelRepository->findOneByField('code', $channelCode);

        if (! $channel) {
            return null;
        }

        $localeCode = core()->getRequestedLocaleCodeInRequestedChannel();

        $locale = $localeCode ? $this->localeRepository->findOneByField('code', $localeCode) : null;

        if (! $locale) {
            return null;
        }

        return ['channel' => $channel, 'locale' => $locale];
    }
}
