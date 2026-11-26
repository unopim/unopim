<?php

namespace Webkul\ProductPassport\Services;

use Webkul\Core\Contracts\Locale;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Contracts\PassportTemplateField;
use Webkul\ProductPassport\DataTransferObjects\PassportPublishRefusal;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Services\PublicAccessGate;

/**
 * The checks a passport publish must pass, shared by the admin and API publish endpoints.
 */
class PassportPublishGuard
{
    public function __construct(
        private readonly PassportReadinessService $readiness,
        private readonly PublicAccessGate $publicAccess,
    ) {}

    /**
     * Why the product's passport cannot be published on the channel in these locales, or null when it can.
     *
     * @param  array<int, int>  $localeIds
     */
    public function refusal(Product $product, Channel $channel, array $localeIds): ?PassportPublishRefusal
    {
        if (! $this->publicAccess->enabledForChannel($channel->code)) {
            return new PassportPublishRefusal('channel_id', trans('passport::app.publications.public-access-disabled'));
        }

        $blockedLocales = $this->blockedLocales($product, $channel, $localeIds);

        if ($blockedLocales !== []) {
            return new PassportPublishRefusal(
                'locale_ids',
                trans('passport::app.catalog.products.edit.passport.publish-blocked'),
                $blockedLocales,
            );
        }

        if (! $this->acceptsNewVersions($product->id, $channel->id)) {
            return new PassportPublishRefusal('status', trans('passport::app.publications.publish-withdrawn'));
        }

        return null;
    }

    /**
     * The requested locales the product cannot be published in yet, with the reason for each.
     *
     * @param  array<int, int>  $localeIds
     * @return array<int, array{locale_code: string, status: string, missing_fields: array<int, array{code: string, label: string, source: string}>}>
     */
    private function blockedLocales(Product $product, Channel $channel, array $localeIds): array
    {
        $locales = $channel->locales()->whereIn('locales.id', $localeIds)->get();

        $assessments = $this->readiness->assessMany($product, $channel, $locales);

        return $locales
            ->reject(fn (Locale $locale): bool => $assessments->get($locale->id)->isReady())
            ->map(function (Locale $locale) use ($assessments): array {
                $assessment = $assessments->get($locale->id);

                return [
                    'locale_code'    => $locale->code,
                    'status'         => $assessment->template === null ? 'missing_template' : 'missing_fields',
                    'missing_fields' => $assessment->missingFields
                        ->map(fn (PassportTemplateField $field): array => [
                            'code'   => $field->code,
                            'label'  => $field->getTranslatedValueWithFallback('label', $locale->code) ?: $field->code,
                            'source' => $field->source_type->value,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Whether the product's passport on this channel can still take a new version.
     *
     * A passport that does not exist yet can, since publishing creates it.
     */
    private function acceptsNewVersions(int $productId, int $channelId): bool
    {
        $status = PublicationProxy::modelClass()::query()
            ->where('product_id', $productId)
            ->where('channel_id', $channelId)
            ->where('type', 'dpp')
            ->value('status');

        return ! $status instanceof PublicationStatus || $status->acceptsNewVersions();
    }
}
