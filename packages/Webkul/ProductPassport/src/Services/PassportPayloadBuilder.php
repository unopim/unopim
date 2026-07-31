<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Contracts\PassportTemplateField as PassportTemplateFieldContract;
use Webkul\ProductPassport\Enums\PassportFieldRole;
use Webkul\ProductPassport\Enums\PassportFieldSource;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Builds the public DPP payload from the passport template bound to the product's
 * attribute family — the leak control. A product whose family has no enabled
 * template publishes no fields at all.
 */
class PassportPayloadBuilder implements PayloadBuilder
{
    private const DOCUMENT_TYPES = ['file', 'image'];

    private const DEFAULT_SECTION = 'passport';

    public function __construct(
        private readonly PassportTemplateResolver $templates,
    ) {}

    public function build(Product $product, PublicationContext $context): array
    {
        $channelCode = $context->channel->code;
        $localeCode = $context->locale->code;

        $template = $this->templates->forProduct($product);

        // resolvedValues() rebuilds a non-memoizing resolver each call; skip the ancestor walk for non-variants.
        $values = empty($product->parent_id) ? ($product->values ?? []) : $product->resolvedValues();

        // Tiers live under `publication.*`: this package's config is merged into the `publication` namespace.
        $order = config('publication.tiers.order', ['consumer']);
        $default = config('publication.tiers.default', 'consumer');

        $tiers = array_fill_keys($order, ['fields' => [], 'documents' => []]);

        $identifier = ['gtin' => null, 'model' => null, 'batch' => null];

        $sections = [];

        foreach ($template?->fields ?? [] as $field) {
            $raw = $this->rawValue($field, $values, $channelCode, $localeCode);

            if ($field->role instanceof PassportFieldRole) {
                $identifier[$field->role->value] = $raw === null || $raw === '' ? null : (string) $raw;

                continue;
            }

            if ($raw === null || $raw === '') {
                continue;
            }

            // Clamp an unknown tier back to `default` so a stale row can't mint an orphan bucket that drops the field.
            $tier = in_array($field->tier->value, $order, true) ? $field->tier->value : $default;

            $label = $field->getTranslatedValueWithFallback('label', $localeCode) ?: '['.$field->code.']';

            $sectionKey = $field->section->code ?? self::DEFAULT_SECTION;

            $sections[$sectionKey] ??= [
                'key'   => $sectionKey,
                'label' => $this->sectionLabel($field, $localeCode),
            ];

            if ($this->isDocument($field)) {
                $tiers[$tier]['documents'][] = $this->document($field, $context, $localeCode, $label, (string) $raw);

                continue;
            }

            $tiers[$tier]['fields'][] = [
                'code'    => $field->code,
                'label'   => $label,
                'value'   => $this->formatValue($field, $raw, $channelCode),
                'section' => $sectionKey,
            ];
        }

        $base = $order[0];

        return [
            'identifier' => $identifier,
            'operator'   => [
                'name'              => (string) (core()->getConfigData('catalog.product_passport.settings.operator_name', $channelCode) ?? ''),
                'address'           => (string) (core()->getConfigData('catalog.product_passport.settings.operator_address', $channelCode) ?? ''),
                'eu_representative' => (string) (core()->getConfigData('catalog.product_passport.settings.operator_eu_rep', $channelCode) ?? ''),
            ],
            // `sections`/`documents` carry the base tier (single-tier shape); `tiers` is the full partition for signed elevation.
            'sections'  => $this->sectionsFor($sections, $tiers[$base]['fields'], $localeCode),
            'documents' => $tiers[$base]['documents'],
            'tiers'     => $tiers,
            // Identity/rebuild metadata ONLY — Publisher excludes `meta` from the checksum, so content placed here never affects dedupe.
            'meta' => [
                'uuid'     => $context->uuid,
                'url'      => $context->url,
                'locale'   => $localeCode,
                'channel'  => $channelCode,
                'built_at' => now()->toIso8601String(),
                'template' => $template?->code,
            ],
        ];
    }

    /**
     * A fixed field publishes the same localized text for every product; an
     * attribute field reads the product's own value for this channel and locale.
     *
     * @param  array<string, mixed>  $values
     */
    private function rawValue(
        PassportTemplateFieldContract $field,
        array $values,
        string $channelCode,
        string $localeCode,
    ): mixed {
        if ($field->source_type === PassportFieldSource::Fixed) {
            return $field->getTranslatedValueWithFallback('fixed_value', $localeCode);
        }

        return $field->attribute?->getValueFromProductValues($values, $channelCode, $localeCode);
    }

    /**
     * Only sections that actually published a field reach the payload, so an empty
     * group never renders as a heading with nothing under it.
     *
     * @param  array<string, array{key: string, label: string}>  $sections
     * @param  list<array{code: string, label: string, value: string, section: string}>  $fields
     * @return list<array{key: string, label: string, fields: list<array<string, string>>}>
     */
    private function sectionsFor(array $sections, array $fields, string $localeCode): array
    {
        $grouped = [];

        foreach ($fields as $field) {
            $grouped[$field['section']][] = $field;
        }

        $payload = [];

        foreach ($sections as $key => $section) {
            if (! isset($grouped[$key])) {
                continue;
            }

            $payload[] = [
                'key'    => $key,
                'label'  => $section['label'] ?: trans('passport::app.public.sections.passport', [], $localeCode),
                'fields' => $grouped[$key],
            ];
        }

        return $payload;
    }

    private function sectionLabel(PassportTemplateFieldContract $field, string $localeCode): string
    {
        $section = $field->section;

        if ($section === null) {
            return trans('passport::app.public.sections.passport', [], $localeCode);
        }

        return $section->getTranslatedValueWithFallback('name', $localeCode) ?: $section->code;
    }

    private function isDocument(PassportTemplateFieldContract $field): bool
    {
        return $field->source_type === PassportFieldSource::Attribute
            && in_array($field->attribute?->type, self::DOCUMENT_TYPES, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(
        PassportTemplateFieldContract $field,
        PublicationContext $context,
        string $localeCode,
        string $label,
        string $raw,
    ): array {
        $kind = $field->attribute?->type === 'image' ? 'image' : 'document';

        /**
         * A preview must not write to the asset disk: the document is emitted with a
         * `preview` marker and no servable path, so the template shows the label as
         * "available after publish" instead of a live download link.
         */
        if ($context->preview) {
            return [
                'code'    => $field->code,
                'label'   => $label,
                'kind'    => $kind,
                'preview' => true,
            ];
        }

        $copiedPath = $this->copyToAssetDisk($context->uuid, $localeCode, $field->code, $raw);

        return array_filter([
            'code'  => $field->code,
            'label' => $label,
            'path'  => $copiedPath,
            'kind'  => $kind,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * Type-aware formatting; a bare `(string)` cast mangles array (multiselect), currency-keyed (price) and boolean values.
     */
    private function formatValue(PassportTemplateFieldContract $field, mixed $value, string $channelCode): string
    {
        $attribute = $field->attribute;

        if (! $attribute instanceof AttributeContract) {
            return (string) $value;
        }

        return match ($attribute->type) {
            'multiselect', 'checkbox' => implode(', ', $this->resolveOptionLabels($attribute, is_array($value) ? $value : explode(',', (string) $value))),
            'select'                  => $this->resolveOptionLabels($attribute, [(string) $value])[0] ?? (string) $value,
            'price'                   => (string) (is_array($value) ? ($value[$channelCode] ?? reset($value) ?: '') : $value),
            'boolean'                 => in_array(strtolower((string) $value), ['true', '1'], true) ? 'true' : 'false',
            'measurement'             => $this->formatMeasurement($value),
            default                   => is_array($value) ? implode(', ', array_map(strval(...), $value)) : (string) $value,
        };
    }

    /**
     * Measurement values are stored as an amount/unit structure, so a bare cast
     * would raise "array to string conversion" and abort the publish.
     */
    private function formatMeasurement(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        $amount = $value['amount'] ?? $value['value'] ?? null;

        if ($amount === null || $amount === '') {
            return '';
        }

        $unit = $value['symbol'] ?? $value['unit'] ?? '';

        return trim($amount.' '.$unit);
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function resolveOptionLabels(AttributeContract $attribute, array $codes): array
    {
        $labelsByCodeAndLocale = [];

        foreach ($attribute->options as $option) {
            foreach ($option->translations as $translation) {
                $labelsByCodeAndLocale[(string) $option->code][$translation->locale] = $translation->label;
            }
        }

        $locale = app()->getLocale();

        return array_map(
            fn (string $code): string => $labelsByCodeAndLocale[$code][$locale] ?? $code,
            array_map('strval', $codes),
        );
    }

    /**
     * Copies the file from the catalog default disk to the asset disk, stamping the final path into the payload.
     *
     * Must run at build time: PublicationVersion::payload is immutable once the version row exists, so no path can be rewritten later.
     */
    private function copyToAssetDisk(string $uuid, string $localeCode, string $fieldCode, string $sourcePath): ?string
    {
        $source = Storage::disk(config('filesystems.default'));

        if (! $source->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetPath = "publication/{$uuid}/{$localeCode}/{$fieldCode}".($extension !== '' ? ".{$extension}" : '');

        $target = Storage::disk(config('publication.asset_disk'));

        if (! $target->exists($targetPath)) {
            $target->put($targetPath, $source->get($sourcePath));
        }

        return $targetPath;
    }
}
