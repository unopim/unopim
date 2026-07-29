<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Webkul\Core\Models\ChannelProxy;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationVersionProxy;
use Webkul\Publication\Models\PublicationViewStatProxy;

/**
 * Computes the locale x status matrix in a fixed number of queries, never one per locale in a loop.
 */
class PassportPanelComposer
{
    private const HISTORY_LIMIT = 25;

    public function __construct(
        private readonly PassportReadinessService $readiness,
    ) {}

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

        $carrierLink = $publication === null
            ? null
            : route('publication.public.dpp.carrier', ['uuid' => $publication->uuid]);

        $rows = $channel->locales->map(function ($locale) use ($product, $channel, $currentByLocale, $signedLink, $carrierLink): array {
            $version = $currentByLocale->get($locale->id);

            $missing = $this->readiness->missingLabels($product, $channel, $locale);

            return [
                'locale_id'      => $locale->id,
                'locale_code'    => $locale->code,
                'version'        => $version?->version,
                'published_at'   => $version?->published_at,
                'missing_count'  => count($missing),
                'missing_fields' => $missing,
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

        $versions = $publication === null ? collect() : PublicationVersionProxy::modelClass()::query()
            ->where('publication_id', $publication->id)
            ->with(['locale', 'publishedBy'])
            ->orderByDesc('published_at')
            ->orderByDesc('version')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $view->with([
            'passportChannel'        => $channel,
            'passportRows'           => $rows,
            'passportPublishedCount' => $rows->whereNotNull('version')->count(),
            'passportViews'          => $passportViews,
            'passportVersions'       => $versions,
            'passportHistoryTotal'   => $publication === null ? 0 : $publication->versions()->count(),
            'passportRepublishUrl'   => $publication === null ? null : route('admin.catalog.passports.republish', $publication->id),
            'passportCanPublish'     => bouncer()->hasPermission('catalog.passport.publish'),
            'passportHistoryUrl'     => $publication === null ? null : route('admin.catalog.passports.versions', $publication->id),
            'passportEnabled'        => (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false),
            'passportAutoPublish'    => (bool) (core()->getConfigData('catalog.product_passport.settings.auto_publish', $channel->code) ?? false),
        ]);
    }
}
