<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Support\Collection;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;
use Webkul\ProductPassport\Contracts\PassportTemplateField as PassportTemplateFieldContract;
use Webkul\ProductPassport\DataTransferObjects\PassportReadiness;
use Webkul\ProductPassport\Enums\PassportFieldSource;

/**
 * Answers whether a product carries everything its passport template marks as
 * required, which is what "legally publishable" means for a DPP.
 */
class PassportReadinessService
{
    public function __construct(
        private readonly PassportTemplateResolver $templates,
    ) {}

    /**
     * The required fields that resolve to nothing for this channel and locale. A
     * product whose family has no template is never publishable, so its whole
     * required set counts as missing (an empty collection then means "no template").
     *
     * @return Collection<int, PassportTemplateFieldContract>
     */
    public function missingFor(Product $product, Channel $channel, Locale $locale): Collection
    {
        return $this->assess($product, $channel, $locale)->missingFields;
    }

    public function isReady(Product $product, Channel $channel, Locale $locale): bool
    {
        return $this->assess($product, $channel, $locale)->isReady();
    }

    /**
     * Labels of the missing fields, for the admin surfaces that explain why a
     * product cannot publish.
     *
     * @return list<string>
     */
    public function missingLabels(Product $product, Channel $channel, Locale $locale): array
    {
        return $this->assess($product, $channel, $locale)->missingFields
            ->map(fn (PassportTemplateFieldContract $field): string => $field->getTranslatedValueWithFallback('label', $locale->code) ?: $field->code)
            ->all();
    }

    public function assess(Product $product, Channel $channel, Locale $locale): PassportReadiness
    {
        $template = $this->templates->forProduct($product);

        if ($template === null) {
            return new PassportReadiness(null, collect());
        }

        $values = empty($product->parent_id) ? ($product->values ?? []) : $product->resolvedValues();

        return $this->assessValues($template, $values, $channel, $locale);
    }

    /**
     * @param  Collection<int, Locale>  $locales
     * @return Collection<int, PassportReadiness>
     */
    public function assessMany(Product $product, Channel $channel, Collection $locales): Collection
    {
        $template = $this->templates->forProduct($product);

        if ($template === null) {
            return $locales->mapWithKeys(
                fn (Locale $locale): array => [$locale->id => new PassportReadiness(null, collect())]
            );
        }

        $values = empty($product->parent_id) ? ($product->values ?? []) : $product->resolvedValues();

        return $locales->mapWithKeys(
            fn (Locale $locale): array => [$locale->id => $this->assessValues($template, $values, $channel, $locale)]
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function assessValues(
        PassportTemplateContract $template,
        array $values,
        Channel $channel,
        Locale $locale,
    ): PassportReadiness {
        $missing = $template->fields
            ->filter(fn (PassportTemplateFieldContract $field): bool => (bool) $field->is_required)
            ->reject(fn (PassportTemplateFieldContract $field): bool => $this->resolves($field, $values, $channel->code, $locale->code))
            ->values();

        return new PassportReadiness($template, $missing);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function resolves(
        PassportTemplateFieldContract $field,
        array $values,
        string $channelCode,
        string $localeCode,
    ): bool {
        $raw = $field->source_type === PassportFieldSource::Fixed
            ? $field->getTranslatedValueWithFallback('fixed_value', $localeCode)
            : $field->attribute?->getValueFromProductValues($values, $channelCode, $localeCode);

        return $raw !== null && $raw !== '' && $raw !== [];
    }
}
