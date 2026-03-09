<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class AttributeMeasurementApiController extends Controller
{
    protected $attributeRepository;

    protected $familyRepository;

    public function __construct(
        AttributeMeasurementRepository $attributeRepository,
        MeasurementFamilyRepository $familyRepository
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->familyRepository = $familyRepository;
    }

    public function getUnitsByFamily($familyCode)
    {
        $units = $this->familyRepository->getUnitsByFamilyCode($familyCode);

        return response()->json([
            'success' => true,
            'count'   => count($units),
            'data'    => $units,
        ]);
    }

    public function store($attributeId)
    {
        $this->attributeRepository->saveAttributeMeasurement($attributeId, [
            'family_code' => request('family_code'),
            'unit_code'   => request('unit_code'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute measurement stored successfully',
        ]);
    }

    public function update($attributeId)
    {
        $this->attributeRepository->saveAttributeMeasurement($attributeId, [
            'family_code' => request('family_code'),
            'unit_code'   => request('unit_code'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute measurement updated successfully',
        ]);
    }
}
