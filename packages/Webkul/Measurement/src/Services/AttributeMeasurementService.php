<?php

namespace Webkul\Measurement\Services;

use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class AttributeMeasurementService
{
    public function __construct(
        protected MeasurementFamilyRepository $familyRepository,
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Build the measurement configuration payload for the given attribute.
     *
     * Returns the family options (with localized units), the saved family
     * code and the saved unit code so the data can be rendered inline on the
     * attribute edit page or returned as JSON.
     *
     * @param  int|string  $attributeId
     */
    public function buildPayload($attributeId): array
    {
        $currentLocale = app()->getLocale();
        $currentLang = strtok($currentLocale, '_');

        $families = $this->familyRepository->all();

        $familyOptions = $families->map(function ($f) use ($currentLocale, $currentLang) {
            return [
                'id'    => $f->code,
                'label' => $f->code,
                'units' => collect($f->units ?? [])->map(function ($u) use ($currentLocale, $currentLang) {

                    $labels = $u['labels'] ?? [];

                    if (isset($labels[$currentLocale])) {
                        $label = $labels[$currentLocale];
                    } elseif ($firstLangMatch = collect($labels)
                        ->first(fn ($_, $key) => str_starts_with($key, $currentLang))) {
                        $label = $firstLangMatch;
                    } else {
                        $label = $u['code'];
                    }

                    return [
                        'id'    => $u['code'],
                        'label' => $label,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $measurement = $this->attributeMeasurementRepository
            ->getByAttributeId($attributeId);

        return [
            'familyOptions' => $familyOptions,
            'oldFamily'     => $measurement->family_code ?? '',
            'oldUnit'       => $measurement->unit_code ?? '',
        ];
    }
}
