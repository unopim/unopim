<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class AttributeMeasurementApiController extends Controller
{
    /**
     * Attribute measurement repository instance.
     */
    protected $attributeRepository;

    /**
     * Measurement family repository instance.
     */
    protected $familyRepository;

    public function __construct(
        AttributeMeasurementRepository $attributeRepository,
        MeasurementFamilyRepository $familyRepository
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->familyRepository = $familyRepository;
    }

    /**
     * Get measurement units by family code.
     *
     * @param  string  $familyCode
     * @return JsonResponse
     */
    public function getUnitsByFamily($familyCode)
    {
        $units = $this->familyRepository->getUnitsByFamilyCode($familyCode);

        return response()->json([
            'success' => true,
            'count'   => count($units),
            'data'    => $units,
        ]);
    }

    /**
     * Get the measurement configuration saved for an attribute.
     *
     * @param  int|string  $attributeId
     * @return JsonResponse
     */
    public function show($attributeId)
    {
        $config = $this->attributeRepository->getByAttributeId($attributeId);

        if (! $config) {
            return response()->json([
                'success' => false,
                'message' => 'No measurement configuration found for this attribute',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $config,
        ]);
    }

    /**
     * Store attribute measurement configuration.
     *
     * @param  int|string  $attributeId
     * @return JsonResponse
     */
    public function store($attributeId)
    {
        return $this->save($attributeId, 'stored');
    }

    /**
     * Update attribute measurement configuration.
     *
     * @param  int|string  $attributeId
     * @return JsonResponse
     */
    public function update($attributeId)
    {
        return $this->save($attributeId, 'updated');
    }

    /**
     * Validate and persist the attribute measurement configuration.
     *
     * @param  int|string  $attributeId
     * @param  string  $action
     * @return JsonResponse
     */
    protected function save($attributeId, $action)
    {
        $data = request()->validate([
            'family_code' => ['required', 'string'],
            'unit_code'   => ['required', 'string'],
        ]);

        $family = $this->familyRepository->findOneWhere(['code' => $data['family_code']]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        if (! collect($family->units ?? [])->contains('code', $data['unit_code'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unit code does not belong to the given measurement family',
            ], 422);
        }

        try {
            $this->attributeRepository->saveAttributeMeasurement($attributeId, [
                'family_code' => $data['family_code'],
                'unit_code'   => $data['unit_code'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Attribute measurement {$action} successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
