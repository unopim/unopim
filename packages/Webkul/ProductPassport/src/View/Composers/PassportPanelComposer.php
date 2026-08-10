<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Webkul\Core\Models\ChannelProxy;
use Webkul\ProductPassport\DataTransferObjects\PassportReadiness;
use Webkul\ProductPassport\Enums\PassportFieldSource;
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

        $assessments = $this->readiness->assessMany($product, $channel, $channel->locales);

        $attributeGroupCodes = $this->attributeGroupCodes(
            (int) $product->attribute_family_id,
            $assessments
                ->flatMap(fn (PassportReadiness $assessment): Collection => $assessment->missingFields)
                ->where('source_type', PassportFieldSource::Attribute)
                ->pluck('attribute_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
        );

        $canEditTemplate = bouncer()->hasPermission('catalog.passport.template.edit');
        $canViewTemplate = bouncer()->hasPermission('catalog.passport.template.view');

        $rows = $channel->locales->map(function ($locale) use ($product, $channel, $currentByLocale, $signedLink, $carrierLink, $assessments, $attributeGroupCodes, $canEditTemplate, $canViewTemplate): array {
            $version = $currentByLocale->get($locale->id);
            $assessment = $assessments->get($locale->id);
            $templateEditUrl = $assessment->template !== null && $canEditTemplate
                ? route('admin.catalog.passports.templates.edit', $assessment->template->id)
                : null;
            $missingFields = $assessment->missingFields->map(function ($field) use ($product, $channel, $locale, $attributeGroupCodes, $templateEditUrl): array {
                $attributeId = $field->source_type === PassportFieldSource::Attribute
                    ? (int) $field->attribute_id
                    : null;
                $groupCode = $attributeId !== null ? ($attributeGroupCodes[$attributeId] ?? null) : null;

                return [
                    'code'       => $field->code,
                    'label'      => $field->getTranslatedValueWithFallback('label', $locale->code) ?: $field->code,
                    'source'     => $field->source_type->label(),
                    'action_url' => $attributeId !== null && $groupCode !== null
                        ? route('admin.catalog.products.edit', [
                            'id'      => $product->id,
                            'channel' => $channel->code,
                            'locale'  => $locale->code,
                            'group'   => $groupCode,
                        ]).'#attribute-'.$attributeId
                        : $templateEditUrl,
                ];
            })->all();

            return [
                'locale_id'       => $locale->id,
                'locale_code'     => $locale->code,
                'version'         => $version?->version,
                'published_at'    => $version?->published_at,
                'ready'           => $assessment->isReady(),
                'missing_count'   => $assessment->template === null ? null : count($missingFields),
                'missing_fields'  => $missingFields,
                'template_url'    => $templateEditUrl
                    ?? ($canViewTemplate
                        ? route('admin.catalog.passports.templates.index')
                        : null),
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
            'passportOffline'        => $publication !== null && ! $publication->status->acceptsNewVersions(),
            'passportStatusLabel'    => $publication === null ? null : trans($publication->status->label()),
            'passportHistoryUrl'     => $publication === null ? null : route('admin.catalog.passports.versions', $publication->id),
            'passportEnabled'        => true,
            'passportAutoPublish'    => $this->feature->autoPublishEnabledFor($channel),
            'passportPublicAccess'   => $this->publicAccess->enabledForChannel($channel->code),
            'passportSettingsUrl'    => bouncer()->hasPermission('configuration.system_settings.publication')
                ? route('admin.settings.system.edit', ['key' => 'digital_product_passport.publication'])
                : null,
        ]);
    }

    /**
     * @param  list<int>  $attributeIds
     * @return array<int, string>
     */
    private function attributeGroupCodes(int $familyId, array $attributeIds): array
    {
        if ($attributeIds === []) {
            return [];
        }

        return DB::table('attribute_group_mappings as mappings')
            ->join(
                'attribute_family_group_mappings as family_groups',
                'family_groups.id',
                '=',
                'mappings.attribute_family_group_id',
            )
            ->join('attribute_groups as groups', 'groups.id', '=', 'family_groups.attribute_group_id')
            ->where('family_groups.attribute_family_id', $familyId)
            ->whereIn('mappings.attribute_id', $attributeIds)
            ->orderBy('family_groups.position')
            ->orderBy('mappings.position')
            ->get(['mappings.attribute_id', 'groups.code'])
            ->unique('attribute_id')
            ->mapWithKeys(fn ($row): array => [(int) $row->attribute_id => $row->code])
            ->all();
    }
}
