<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Builds the public DPP payload from the product's `dpp` attribute group
 * only — the leak control. See `groupAttributesFor()` for why this is the
 * single point every field/document must pass through.
 */
class PassportPayloadBuilder implements PayloadBuilder
{
    private const GROUP_CODE = 'dpp';

    private const DOCUMENT_TYPES = ['file', 'image'];

    /** Rendered in the dedicated identifier block, so kept out of the field list. */
    private const IDENTIFIER_CODES = ['dpp_gtin', 'dpp_model_identifier', 'dpp_batch_identifier'];

    public function build(Product $product, PublicationContext $context): array
    {
        $channelCode = $context->channel->code;
        $localeCode = $context->locale->code;

        $attributes = $this->groupAttributesFor($product);

        // Only pay the ancestor-walk cost when there is an ancestor to walk:
        // resolvedValues() constructs a fresh VariantValueResolver on every
        // call (it is container-bound via bind(), not singleton() — the
        // resolver's own $memo never survives past the single call it's
        // built for), so it is pure overhead for the overwhelming majority
        // of products, which are not variants.
        $values = empty($product->parent_id) ? ($product->values ?? []) : $product->resolvedValues();

        // Tiers live under `publication.tiers`: the ProductPassport provider
        // merges this package's config into the `publication` namespace (see
        // ProductPassportServiceProvider::register), so there is no top-level
        // `passport` config key.
        $order = config('publication.tiers.order', ['consumer']);
        $default = config('publication.tiers.default', 'consumer');
        $map = config('publication.tiers.map', []);

        // Clamp any misconfigured tier back to `default` so a typo in the map
        // can never mint an orphan bucket that array_slice-by-order silently
        // drops (which would hide a field from every tier including authority).
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
                $copiedPath = $this->copyToAssetDisk($context->uuid, $localeCode, $attribute->code, (string) $raw);

                if ($copiedPath !== null) {
                    $tiers[$tier]['documents'][] = ['code' => $attribute->code, 'label' => $label, 'path' => $copiedPath];
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
            // `sections[0].fields` and `documents` carry the base (consumer)
            // tier verbatim — the template and JSON-LD resource already read
            // exactly these, so an empty tiers map keeps today's single-tier
            // shape byte-for-byte. `tiers` is the full partition the controller
            // reads to elevate a signed request to operator/authority.
            'sections'  => [['key' => self::GROUP_CODE, 'label' => trans('passport::app.public.sections.passport', [], $localeCode), 'fields' => $tiers[$base]['fields']]],
            'documents' => $tiers[$base]['documents'],
            'tiers'     => $tiers,
            // Identity/rebuild metadata ONLY — Publisher::publish() excludes
            // the entire `meta` key from the checksum (Arr::except($payload,
            // 'meta')), so anything content-bearing placed here is invisible
            // to dedupe by construction. Never move a field from `sections`
            // or `documents` into `meta` to "simplify" the payload.
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
     * The leak control: only members of the `dpp` group, scoped to this
     * product's own attribute family, ever reach a payload.
     * `AttributeGroup::customAttributes($familyId)` — not the
     * `custom_attributes` *property*, which silently returns NULL — already
     * orders by `attribute_group_mappings.position`, so no attribute reorder
     * in the admin UI can ever mint a spurious new version of byte-identical
     * content.
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
     * Resolves a passport field's raw value, honouring the admin field-mapping:
     * when `catalog.product_passport.mapping.<dpp_code>` names an existing
     * source attribute (per-scope, so `getConfigData` channel-fallback applies)
     * and that source carries a non-empty value, that value is used; otherwise
     * the `dpp_*` attribute's own value is the fallback — an unset mapping is
     * fully backward-compatible. Only the single mapped value is read: the
     * field's code, label and formatting stay driven by the `dpp_*` attribute,
     * so a mapping never widens the public surface beyond the `dpp` group.
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
     * Typed formatting, never a bare `(string)` cast: `(string) []` silently
     * yields the literal string `"Array"` for a multiselect/checkbox value
     * that resolves to an array, a price value is keyed by currency code
     * (not a scalar), and a boolean is stored as the string `"1"`/`"0"` or
     * `"true"`/`"false"` depending on write path.
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
     * Copies the referenced file from wherever the catalog attribute stored
     * it (the shared, public-facing default disk — see Task 6) onto the
     * dedicated asset disk, stamping the FINAL, already-servable path into
     * the payload. This must happen here, at build time: `PublicationVersion
     * ::payload` is immutable the instant the version row is created, so
     * nothing downstream of this method can ever rewrite a
     * `documents[].path` value.
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
