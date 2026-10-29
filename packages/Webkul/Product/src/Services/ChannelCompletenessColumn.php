<?php

namespace Webkul\Product\Services;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * Resolves whether the current request is explicitly scoped to one channel
 * (e.g. a dashboard drill-down link landing on the product grid with
 * ?channel=<code>) for the purposes of the "completeness" property.
 *
 * Used by both `ProductDataGrid::prepareQueryBuilder()` (to decide whether to
 * join `product_completeness` and which column to SELECT/ORDER BY) and
 * `CompletenessFilter` (to decide which column to filter WHERE on). Both
 * callers MUST share this single source of truth: the filter assumes the
 * `product_completeness` join already exists whenever this resolves to a
 * result, and the grid is the one place that join actually gets added.
 */
class ChannelCompletenessColumn
{
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
    ) {}

    /**
     * @return array{channel: Channel, locale: Locale}|null Null means: no
     *                                                      channel was explicitly requested, or it (or its locale)
     *                                                      couldn't be resolved — callers should fall back to the
     *                                                      product's pre-computed cross-channel average.
     */
    public function resolve(): ?array
    {
        $channelCode = core()->getRequestedChannelCode(false);

        if (! $channelCode) {
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
