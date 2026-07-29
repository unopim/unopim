<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Builds the public DPP payload from the product's `dpp` attribute group only — the leak control.
 */
class PassportPayloadBuilder implements PayloadBuilder
{
    private const GROUP_CODE = 'dpp';

    private const DOCUMENT_TYPES = ['file', 'image'];

    /** Merchant-defined passport fields; see PassportMappingController. */
    private const CUSTOM_FIELDS_KEY = 'catalog.product_passport.custom_fields';

    /** Rendered in the dedicated identifier block, so kept out of the field list. */
    private const IDENTIFIER_CODES = ['dpp_gtin', 'dpp_model_identifier', 'dpp_batch_identifier'];

    public function build(Product $product, PublicationContext $context): array
    {
        $channelCode = $context->channel->code;
        $localeCode = $context->locale->code;

        $attributes = $this->groupAttributesFor($product);

        // resolvedValues() rebuilds a non-memoizing resolver each call; skip the ancestor walk for non-variants.
        $values = empty($product->parent_id) ? ($product->values ?? []) : $product->resolvedValues();

        // Tiers live under `publication.*`: this package's config is merged into the `publication` namespace.
        $order = config('publication.tiers.order', ['consumer']);
        $default = config('publication.tiers.default', 'consumer');
        $map = config('publication.tiers.map', []);

        // Clamp an unknown tier back to `default` so a map typo can't mint an orphan bucket that drops the field.
        $tierOf = fn (string $code): string => in_array($map[$code] ?? $default, $order, true) ? ($map[$code] ?? $default) : $default;

        $tiers = array_fill_keys($order, ['fields' => [], 'documents' => []]);

        foreach ($attributes as $attribute) {
            if (in_array($attribute->code, self::IDENTIFIER_CODES, true)) {
                continue;
            }

            $raw = $this->mappedRaw($attribute, $attributes, $values, $channelCode, $localeCode);

            if ($raw === null || $raw === '') {
                continue;
            }

            $label = $attribute->getTranslatedValueWithFallback('name', $localeCode) ?: '['.$attribute->code.']';
            $tier = $tierOf($attribute->code);

            if (in_array($attribute->type, self::DOCUMENT_TYPES, true)) {
                // Preview builds must not write to the asset disk: emit the doc with a
                // `preview` marker and no servable path so the template shows the label
                // as "available after publish" instead of a live download link.
                if ($context->preview) {
                    $tiers[$tier]['documents'][] = [
                        'code'    => $attribute->code,
                        'label'   => $label,
                        'kind'    => $attribute->type === 'image' ? 'image' : 'document',
                        'preview' => true,
                    ];

                    continue;
                }

                $copiedPath = $this->copyToAssetDisk($context->uuid, $localeCode, $attribute->code, (string) $raw);

                if ($copiedPath !== null) {
                    // `kind` lets the public template render an image inline (<img>) while a file stays a download link.
                    $tiers[$tier]['documents'][] = [
                        'code'  => $attribute->code,
                        'label' => $label,
                        'path'  => $copiedPath,
                        'kind'  => $attribute->type === 'image' ? 'image' : 'document',
                    ];
                }

                continue;
            }

            $tiers[$tier]['fields'][] = [
                'code'  => $attribute->code,
                'label' => $label,
                'value' => $this->formatValue($attribute, $raw, $channelCode),
            ];
        }

        $base = $order[0];

        // Merchant-defined fields ride the base (consumer) tier, never gated to operator/authority.
        $this->appendCustomFields($tiers[$base]['fields'], $values, $channelCode, $localeCode);

        return [
            'identifier' => [
                'gtin'  => $this->identifierValue($attributes, $values, $channelCode, $localeCode, 'dpp_gtin'),
                'model' => $this->identifierValue($attributes, $values, $channelCode, $localeCode, 'dpp_model_identifier'),
                'batch' => $this->identifierValue($attributes, $values, $channelCode, $localeCode, 'dpp_batch_identifier'),
            ],
            'operator' => [
                'name'              => (string) (core()->getConfigData('catalog.product_passport.settings.operator_name', $channelCode) ?? ''),
                'address'           => (string) (core()->getConfigData('catalog.product_passport.settings.operator_address', $channelCode) ?? ''),
                'eu_representative' => (string) (core()->getConfigData('catalog.product_passport.settings.operator_eu_rep', $channelCode) ?? ''),
            ],
            // `sections[0]`/`documents` carry the base tier verbatim (single-tier shape); `tiers` is the full partition for signed elevation.
            'sections'  => [['key' => self::GROUP_CODE, 'label' => trans('passport::app.public.sections.passport', [], $localeCode), 'fields' => $tiers[$base]['fields']]],
            'documents' => $tiers[$base]['documents'],
            'tiers'     => $tiers,
            // Identity/rebuild metadata ONLY — Publisher excludes `meta` from the checksum, so content placed here never affects dedupe.
            'meta' => [
                'uuid'     => $context->uuid,
                'url'      => $context->url,
                'locale'   => $localeCode,
                'channel'  => $channelCode,
                'built_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * The leak control: only `dpp`-group attributes scoped to the product's family reach a payload.
     *
     * Uses customAttributes($familyId) (the property returns NULL); its position ordering keeps content byte-stable across UI reorders.
     *
     * @return Collection<int, AttributeContract>
     */
    private function groupAttributesFor(Product $product): Collection
    {
        $group = AttributeGroupProxy::modelClass()::query()->where('code', self::GROUP_CODE)->first();

        return $group === null ? collect() : $group->customAttributes($product->attribute_family_id);
    }

    private function identifierValue(Collection $attributes, array $values, string $channelCode, string $localeCode, string $code): ?string
    {
        $attribute = $attributes->firstWhere('code', $code);

        if ($attribute === null) {
            return null;
        }

        $raw = $this->mappedRaw($attribute, $attributes, $values, $channelCode, $localeCode);

        return $raw === null ? null : (string) $raw;
    }

    /**
     * Resolves a field's raw value, honouring the admin field-mapping.
     *
     * A mapped source attribute's non-empty value wins; otherwise the `dpp_*` attribute's own value. Only the value is
     * borrowed — code/label/formatting stay driven by the `dpp_*` attribute, so a mapping never widens the public surface.
     *
     * @param  Collection<int, AttributeContract>  $attributes
     * @param  array<string, mixed>  $values
     */
    private function mappedRaw(
        AttributeContract $attribute,
        Collection $attributes,
        array $values,
        string $channelCode,
        string $localeCode,
    ): mixed {
        $sourceCode = core()->getConfigData('catalog.product_passport.mapping.'.$attribute->code, $channelCode);

        if (! empty($sourceCode) && $sourceCode !== $attribute->code) {
            $source = $attributes->firstWhere('code', $sourceCode)
                ?? AttributeProxy::modelClass()::query()->where('code', $sourceCode)->first();

            $sourceRaw = $source?->getValueFromProductValues($values, $channelCode, $localeCode);

            if ($sourceRaw !== null && $sourceRaw !== '') {
                return $sourceRaw;
            }
        }

        return $attribute->getValueFromProductValues($values, $channelCode, $localeCode);
    }

    /**
     * Append the merchant's custom fields to the consumer field list.
     *
     * Each `{name, attribute}` publishes the source attribute's value under the user-typed label; empty values are skipped.
     *
     * @param  list<array{code: string, label: string, value: string}>  $fields
     * @param  array<string, mixed>  $values
     */
    private function appendCustomFields(array &$fields, array $values, string $channelCode, string $localeCode): void
    {
        $raw = core()->getConfigData(self::CUSTOM_FIELDS_KEY, $channelCode);

        $rows = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);

        if (! is_array($rows) || $rows === []) {
            return;
        }

        $sourceCodes = array_values(array_filter(array_map(
            fn ($row): string => is_array($row) ? (string) ($row['attribute'] ?? '') : '',
            $rows,
        )));

        if ($sourceCodes === []) {
            return;
        }

        $sources = AttributeProxy::modelClass()::query()
            ->whereIn('code', $sourceCodes)
            ->get()
            ->keyBy('code');

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $sourceCode = (string) ($row['attribute'] ?? '');
            $source = $sources->get($sourceCode);

            if ($name === '' || ! $source instanceof AttributeContract) {
                continue;
            }

            $value = $source->getValueFromProductValues($values, $channelCode, $localeCode);

            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = [
                'code'  => 'custom_'.Str::slug($name, '_'),
                'label' => $name,
                'value' => $this->formatValue($source, $value, $channelCode),
            ];
        }
    }

    /**
     * Type-aware formatting; a bare `(string)` cast mangles array (multiselect), currency-keyed (price) and boolean values.
     */
    private function formatValue(AttributeContract $attribute, mixed $value, string $channelCode): string
    {
        return match ($attribute->type) {
            'multiselect', 'checkbox' => implode(', ', $this->resolveOptionLabels($attribute, is_array($value) ? $value : explode(',', (string) $value))),
            'select'                  => $this->resolveOptionLabels($attribute, [(string) $value])[0] ?? (string) $value,
            'price'                   => (string) (is_array($value) ? ($value[$channelCode] ?? reset($value) ?: '') : $value),
            'boolean'                 => in_array(strtolower((string) $value), ['true', '1'], true) ? 'true' : 'false',
            default                   => (string) $value,
        };
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
    private function copyToAssetDisk(string $uuid, string $localeCode, string $attributeCode, string $sourcePath): ?string
    {
        $source = Storage::disk(config('filesystems.default'));

        if (! $source->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetPath = "publication/{$uuid}/{$localeCode}/{$attributeCode}".($extension !== '' ? ".{$extension}" : '');

        $target = Storage::disk(config('publication.asset_disk'));

        if (! $target->exists($targetPath)) {
            $target->put($targetPath, $source->get($sourcePath));
        }

        return $targetPath;
    }
}
