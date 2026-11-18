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
     * @param  int|string  $attributeId
     */
    public function buildPayload($attributeId): array
    {
        $measurement = $this->attributeMeasurementRepository->getByAttributeId($attributeId);

        $savedFamily = $measurement->family_code ?? '';

        return [
            'familyOptions' => $this->familyOptions(),
            'oldFamily'     => $savedFamily,
            'oldUnit'       => $measurement->unit_code ?? '',
            'units'         => $savedFamily === '' ? [] : $this->unitsForFamily($savedFamily),
        ];
    }

    /**
     * All measurement families as select options (code as both id and label).
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function familyOptions(): array
    {
        return $this->familyRepository
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
