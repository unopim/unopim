<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Services\PassportFeature;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationVersionProxy;
use Webkul\Publication\Models\PublicationViewStatProxy;
use Webkul\Publication\Services\PublicAccessGate;

/**
 * Computes the locale x status matrix in a fixed number of queries, never one per locale in a loop.
 */
class PassportPanelComposer
{
    private const int HISTORY_LIMIT = 25;

    public function __construct(
        private readonly PassportReadinessService $readiness,
        private readonly PassportFeature $feature,
        private readonly PublicAccessGate $publicAccess,
    ) {}

    /**
     * Bind the passport panel's locale rows, links and counters to the product edit view.
     *
     * Signed operator/authority links are the only way to reveal those tiers, so they are minted
     * server-side and only once a version is live. The preview link is admin-only and side-effect
     * free, so it is offered whether or not a version exists yet.
     */
    public function compose(View $view): void
    {
        $product = $view->getData()['product'];

        $channel = ChannelProxy::modelClass()::query()
            ->where('code', core()->getRequestedChannelCode())
            ->with('locales')
            ->first();

        if ($channel === null || ! $this->feature->enabledFor($channel)) {
            $view->with([
                'passportChannel' => $channel,
                'passportEnabled' => false,
                'passportRows'    => collect(),
            ]);

            return;
        }

        $publication = PublicationProxy::modelClass()::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->where('type', 'dpp')
            ->with(['versions' => fn (Relation $query) => $query->where('is_current', true)->with('locale')])
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

        $readiness = $this->readiness->rowsFor($product, $channel);

        $rows = $channel->locales->map(function (Locale $locale) use ($product, $channel, $currentByLocale, $signedLink, $carrierLink, $readiness): array {
            $version = $currentByLocale->get($locale->id);

            return [
                'locale_id'       => $locale->id,
                'locale_code'     => $locale->code,
                'version'         => $version?->version,
                'published_at'    => $version?->published_at,
                ...$readiness->get($locale->id),
                'operator_link'  => $version !== null ? $signedLink($locale->code, 'operator') : null,
                'authority_link' => $version !== null ? $signedLink($locale->code, 'authority') : null,
                'carrier_link'   => $version !== null ? $carrierLink : null,
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
            'passportChannel'         => $channel,
            'passportRows'            => $rows,
            'passportCurrentLocaleId' => core()->getRequestedLocale()?->id,
            'passportReadinessUrl'    => route('admin.catalog.products.passport.show', ['product' => $product->id, 'channel' => $channel->code]),
            'passportPublishedCount'  => $rows->whereNotNull('version')->count(),
            'passportViews'           => $passportViews,
            'passportVersions'        => $versions,
            'passportHistoryTotal'    => $publication === null ? 0 : $publication->versions()->count(),
            'passportRepublishUrl'    => $publication === null ? null : route('admin.catalog.passports.republish', $publication->id),
            'passportCanPublish'      => bouncer()->hasPermission('catalog.passport.publish'),
            'passportOffline'         => $publication !== null && ! $publication->status->acceptsNewVersions(),
            'passportStatusLabel'     => $publication === null ? null : trans($publication->status->label()),
            'passportHistoryUrl'      => $publication === null ? null : route('admin.catalog.passports.versions', $publication->id),
            'passportEnabled'         => true,
            'passportAutoPublish'     => $this->feature->autoPublishEnabledFor($channel),
            'passportPublicAccess'    => $this->publicAccess->enabledForChannel($channel->code),
            'passportSettingsUrl'     => bouncer()->hasPermission('configuration.system_settings.publication')
                ? route('admin.settings.system.edit', ['key' => 'digital_product_passport.publication'])
                : null,
        ]);
    }
}
