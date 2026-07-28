<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Measurement\Http\Requests\FamilyUnitOptionsForm;
use Webkul\Measurement\Services\AttributeMeasurementService;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeMeasurementService $attributeMeasurementService
    ) {}

    /**
     * Unit options of a single measurement family, for the attribute editor's
     * family select. Kept off {@see getAttributeMeasurement()} so the initial
     * payload stays independent of how many families the catalogue holds.
     */
    public function familyUnits(FamilyUnitOptionsForm $request): JsonResponse
    {
        return new JsonResponse([
            'units' => $this->attributeMeasurementService->unitsForFamily(
                (string) $request->validated('family')
            ),
        ]);
    }

    /**
     * Get measurement configuration for the given attribute.
     *
     * @param  int|string  $attributeId
     * @return JsonResponse
     */
    public function getAttributeMeasurement($attributeId)
    {
        try {
            if (! $attributeId) {
                return response()->json([
                    'familyOptions' => [],
                    'oldFamily'     => '',
                    'oldUnit'       => '',
                    'message'       => trans('measurement::app.messages.attribute.invalid_id'),
                ], 400);
            }

            return response()->json(
                $this->attributeMeasurementService->buildPayload($attributeId)
            );

        } catch (\Throwable $e) {

            Log::error('Attribute Measurement fetch failed', [
                'attribute_id' => $attributeId,
                'error'        => trans('measurement::app.messages.family.error'),
            ]);

            return response()->json([
                'familyOptions' => [],
                'oldFamily'     => '',
                'oldUnit'       => '',
                'message'       => trans('measurement::app.messages.attribute.load_failed'),
            ], 500);
        }
    }
}
