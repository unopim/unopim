<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\Support\Facades\URL;
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

        $rows = $channel->locales->map(function ($locale) use ($currentByLocale, $scores, $signedLink, $carrierLink): array {
            $version = $currentByLocale->get($locale->id);
            $score = $scores->get($locale->id);

            return [
                'locale_id'      => $locale->id,
                'locale_code'    => $locale->code,
                'version'        => $version?->version,
                'published_at'   => $version?->published_at,
                'score'          => $score?->score,
                'missing_count'  => $score?->missing_count,
                // Signed elevation links are the ONLY way to reveal
                // operator/authority tiers, and only make sense once a version
                // is live for the locale — minted server-side so no signature
                // is ever constructed in the browser.
                'operator_link'  => $version !== null ? $signedLink($locale->code, 'operator') : null,
                'authority_link' => $version !== null ? $signedLink($locale->code, 'authority') : null,
                // The QR carrier is publication-scoped (one uuid), surfaced per
                // locale row only once that locale has a live version to scan.
                'carrier_link'   => $version !== null ? $carrierLink : null,
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
