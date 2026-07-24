<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\View\View;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Computes the whole locale x status matrix in two queries (channel +
 * locales via one eager load, current versions via a second), never one
 * query per locale inside a loop.
 */
class PassportPanelComposer
{
    public function compose(View $view): void
    {
        $product = $view->getData()['product'];

        $channel = ChannelProxy::modelClass()::query()
            ->where('code', core()->getRequestedChannelCode())
            ->with('locales')
            ->first();

        if ($channel === null) {
            $view->with(['passportChannel' => null, 'passportRows' => collect()]);

            return;
        }

        $publication = PublicationProxy::modelClass()::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->where('type', 'dpp')
            ->with(['versions' => fn ($query) => $query->where('is_current', true)->with('locale')])
            ->first();

        $currentByLocale = $publication?->versions->keyBy('locale_id') ?? collect();

        $scores = ProductCompletenessScore::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->whereIn('locale_id', $channel->locales->pluck('id'))
            ->get()
            ->keyBy('locale_id');

        $rows = $channel->locales->map(function ($locale) use ($currentByLocale, $scores): array {
            $version = $currentByLocale->get($locale->id);
            $score = $scores->get($locale->id);

            return [
                'locale_id'     => $locale->id,
                'locale_code'   => $locale->code,
                'version'       => $version?->version,
                'published_at'  => $version?->published_at,
                'score'         => $score?->score,
                'missing_count' => $score?->missing_count,
            ];
        });

        $view->with([
            'passportChannel'     => $channel,
            'passportRows'        => $rows,
            'passportEnabled'     => (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false),
            'passportAutoPublish' => (bool) (core()->getConfigData('catalog.product_passport.settings.auto_publish', $channel->code) ?? false),
        ]);
    }
}
