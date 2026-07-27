<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Measurement\Services\AttributeMeasurementService;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeMeasurementService $attributeMeasurementService
    ) {}

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
