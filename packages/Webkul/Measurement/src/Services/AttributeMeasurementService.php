<?php

namespace Webkul\Measurement\Services;

use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class AttributeMeasurementService
{
    public function __construct(
        protected MeasurementFamilyRepository $familyRepository,
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Build the measurement configuration payload for the given attribute.
     *
     * Returns the family options, the saved family code and the saved unit code
     * so the data can be rendered inline on the attribute edit page or returned
     * as JSON.
     *
     * Only the saved family carries its units, because that is the only list the
     * page can show before the user picks something; the rest are fetched when a
     * family is chosen. Embedding every family's units instead made the payload
     * grow with the whole measurement catalogue.
     *
     * @param  int|string  $attributeId
     */
    public function buildPayload($attributeId): array
    {
        $measurement = $this->attributeMeasurementRepository->getByAttributeId($attributeId);

        $savedFamily = $measurement->family_code ?? '';

        $familyOptions = $this->familyRepository
            ->getModel()
            ->newQuery()
            ->orderBy('id')
            ->get(['id', 'code'])
            ->map(fn ($family): array => [
                'id'    => $family->code,
                'label' => $family->code,
            ])
            ->values()
            ->toArray();

        return [
            'familyOptions' => $familyOptions,
            'oldFamily'     => $savedFamily,
            'oldUnit'       => $measurement->unit_code ?? '',
            'units'         => $savedFamily === '' ? [] : $this->unitsForFamily($savedFamily),
        ];
    }

    /**
     * Unit options of a family, labelled in the active locale.
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function unitsForFamily(string $familyCode): array
    {
        $family = $this->familyRepository
            ->getModel()
            ->newQuery()
            ->with(['units.translations', 'units.conversions'])
            ->where('code', $familyCode)
            ->first();

        if (! $family) {
            return [];
        }

        $currentLocale = app()->getLocale();
        $currentLang = strtok($currentLocale, '_');

        return collect($family->units ?? [])
            ->map(fn ($unit): array => [
                'id'    => $unit['code'],
                'label' => $this->resolveUnitLabel($unit, $currentLocale, $currentLang),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Falls back from the exact locale to any locale sharing its language, and
     * finally to the unit code, so a unit is never rendered unlabelled.
     */
    protected function resolveUnitLabel(array $unit, string $locale, string $language): string
    {
        $labels = $unit['labels'] ?? [];

        if (isset($labels[$locale])) {
            return $labels[$locale];
        }

        $languageMatch = collect($labels)->first(
            fn ($_, $key): bool => str_starts_with($key, $language)
        );

        return $languageMatch ?? $unit['code'];
    }
}
