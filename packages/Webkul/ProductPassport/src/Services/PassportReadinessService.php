<?php

namespace Webkul\ProductPassport\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * What the product edit panel shows per locale id: whether the locale can be published, which required
     * fields are still missing (each with a link to fix it), and the "Required for DPP" badge of every
     * required attribute. Shared by the initial render and the JSON endpoint the panel re-reads after a save.
     *
     * @return Collection<int, array{ready: bool, missing_count: ?int, missing_fields: list<array<string, mixed>>, requirements: array<int, array{variant: string, title: string}>, template_url: ?string}>
     */
    public function rowsFor(Product $product, Channel $channel): Collection
    {
        $assessments = $this->assessMany($product, $channel, $channel->locales);

        $attributeGroupCodes = $this->attributeGroupCodes(
            (int) $product->attribute_family_id,
            $assessments
                ->flatMap(fn (PassportReadiness $assessment): Collection => $assessment->missingFields)
                ->where('source_type', PassportFieldSource::Attribute)
                ->pluck('attribute_id')
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
        );

        $canEditTemplate = bouncer()->hasPermission('catalog.passport.template.edit');
        $canViewTemplate = bouncer()->hasPermission('catalog.passport.template.view');

        return $channel->locales->mapWithKeys(function (Locale $locale) use ($product, $channel, $assessments, $attributeGroupCodes, $canEditTemplate, $canViewTemplate): array {
            $assessment = $assessments->get($locale->id);
            $templateEditUrl = $assessment->template !== null && $canEditTemplate
                ? route('admin.catalog.passports.templates.edit', $assessment->template->id)
                : null;
            $missingFields = $assessment->missingFields->map(function (PassportTemplateFieldContract $field) use ($product, $channel, $locale, $attributeGroupCodes, $templateEditUrl): array {
                $attributeId = $field->source_type === PassportFieldSource::Attribute
                    ? (int) $field->attribute_id
                    : null;
                $groupCode = $attributeId !== null ? ($attributeGroupCodes[$attributeId] ?? null) : null;

                return [
                    'code'         => $field->code,
                    'attribute_id' => $attributeId,
                    'label'        => $field->getTranslatedValueWithFallback('label', $locale->code) ?: $field->code,
                    'source'       => $field->source_type->label(),
                    'action_url'   => $attributeId !== null && $groupCode !== null
                        ? route('admin.catalog.products.edit', [
                            'id'      => $product->id,
                            'channel' => $channel->code,
                            'locale'  => $locale->code,
                            'group'   => $groupCode,
                        ]).'#attribute-'.$attributeId
                        : $templateEditUrl,
                ];
            })->all();

            $missingAttributeIds = array_column($missingFields, 'attribute_id');

            $requirements = $assessment->template === null ? [] : $assessment->template->fields
                ->where('is_required', true)
                ->where('source_type', PassportFieldSource::Attribute)
                ->whereNotNull('attribute_id')
                ->unique('attribute_id')
                ->mapWithKeys(fn (PassportTemplateFieldContract $field): array => [
                    (int) $field->attribute_id => Arr::only(
                        PassportAttributeRequirements::indicator(in_array((int) $field->attribute_id, $missingAttributeIds, true)),
                        ['variant', 'title'],
                    ),
                ])
                ->all();

            return [$locale->id => [
                'ready'          => $assessment->isReady(),
                'missing_count'  => $assessment->template === null ? null : count($missingFields),
                'missing_fields' => $missingFields,
                'requirements'   => $requirements,
                'template_url'   => $templateEditUrl
                    ?? ($canViewTemplate
                        ? route('admin.catalog.passports.templates.index')
                        : null),
            ]];
        });
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
            ->mapWithKeys(fn (object $row): array => [(int) $row->attribute_id => $row->code])
            ->all();
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
