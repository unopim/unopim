<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationViewStatProxy;

/**
 * Computes the locale x status matrix in a fixed number of queries, never one per locale in a loop.
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

        $signedLink = fn (string $localeCode, string $tier): ?string => $publication === null ? null : URL::temporarySignedRoute(
            'publication.public.dpp.show.locale',
            now()->addDays(30),
            ['uuid' => $publication->uuid, 'locale' => $localeCode, 'tier' => $tier],
        );

        $scores = ProductCompletenessScore::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->whereIn('locale_id', $channel->locales->pluck('id'))
            ->get()
            ->keyBy('locale_id');

        $carrierLink = $publication === null
            ? null
            : route('publication.public.dpp.carrier', ['uuid' => $publication->uuid]);

        $rows = $channel->locales->map(function ($locale) use ($product, $channel, $currentByLocale, $scores, $signedLink, $carrierLink): array {
            $version = $currentByLocale->get($locale->id);
            $score = $scores->get($locale->id);

            return [
                'locale_id'      => $locale->id,
                'locale_code'    => $locale->code,
                'version'        => $version?->version,
                'published_at'   => $version?->published_at,
                'score'          => $score?->score,
                'missing_count'  => $score?->missing_count,
                // Signed links are the only way to reveal operator/authority tiers; minted server-side, only once a version is live.
                'operator_link'  => $version !== null ? $signedLink($locale->code, 'operator') : null,
                'authority_link' => $version !== null ? $signedLink($locale->code, 'authority') : null,
                'carrier_link'   => $version !== null ? $carrierLink : null,
                // Admin-only, side-effect-free render of the current product data; available whether or not a version exists yet.
                'preview_url'    => route('admin.catalog.products.passport.preview', [
                    'product'    => $product->id,
                    'channel_id' => $channel->id,
                    'locale_id'  => $locale->id,
                ]),
            ];
        });

        $passportViews = $publication === null ? 0 : (int) PublicationViewStatProxy::modelClass()::query()
            ->where('publication_id', $publication->id)
            ->sum('views');

        $view->with([
            'passportChannel'     => $channel,
            'passportRows'        => $rows,
            'passportViews'       => $passportViews,
            'passportHistoryUrl'  => $publication === null ? null : route('admin.catalog.passports.versions', $publication->id),
            'passportEnabled'     => (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false),
            'passportAutoPublish' => (bool) (core()->getConfigData('catalog.product_passport.settings.auto_publish', $channel->code) ?? false),
        ]);
    }
}
