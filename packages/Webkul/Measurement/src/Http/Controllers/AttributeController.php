<?php

namespace Webkul\Measurement\Http\Controllers;

use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class AttributeController extends Controller
{
    protected $familyRepository;

    protected $attributeMeasurementRepository;

    public function __construct(
        MeasurementFamilyRepository $familyRepository,
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->familyRepository = $familyRepository;
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    public function getAttributeMeasurement($attributeId)
    {
        try {
            if (! $attributeId) {
                return response()->json([
                    'familyOptions' => [],
                    'oldFamily'     => '',
                    'oldUnit'       => '',
                    'message'       => 'Invalid attribute id.',
                ], 400);
            }

            $currentLocale = app()->getLocale();
            $currentLang = strtok($currentLocale, '_');

            $families = $this->familyRepository->all();

            $familyOptions = $families->map(function ($f) use ($currentLocale, $currentLang) {
                return [
                    'id'    => $f->code,
                    'label' => $f->name,
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

            return response()->json([
                'familyOptions' => $familyOptions,
                'oldFamily'     => $measurement->family_code ?? '',
                'oldUnit'       => $measurement->unit_code ?? '',
            ]);

        } catch (\Throwable $e) {

            \Log::error('Attribute Measurement fetch failed', [
                'attribute_id' => $attributeId,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'familyOptions' => [],
                'oldFamily'     => '',
                'oldUnit'       => '',
                'message'       => 'Unable to load attribute measurement data.',
            ], 500);
        }
    }
}
