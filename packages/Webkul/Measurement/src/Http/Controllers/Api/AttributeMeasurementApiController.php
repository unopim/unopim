<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class AttributeMeasurementApiController extends Controller
{
    public function __construct(
        /**
         * Attribute measurement repository instance.
         */
        protected AttributeMeasurementRepository $attributeRepository,
        /**
         * Measurement family repository instance.
         */
        protected MeasurementFamilyRepository $familyRepository,
        /**
         * Attribute repository instance.
         */
        protected AttributeRepository $attributeMasterRepository
    ) {}

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
     * @param  string  $attributeCode
     * @return JsonResponse
     */
    public function show($attributeCode)
    {
        $attribute = $this->attributeMasterRepository->findOneByField('id', $attributeCode);

        if (! $attribute) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.attribute.not_found'),
            ], 404);
        }

        $config = $this->attributeRepository->getByAttributeId($attribute->id);

        if (! $config) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.attribute.config_not_found'),
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
     * @param  string  $attributeCode
     * @return JsonResponse
     */
    public function store($attributeCode)
    {
        return $this->save($attributeCode, 'measurement::app.messages.attribute.config_created');
    }

    /**
     * Update attribute measurement configuration.
     *
     * @param  string  $attributeCode
     * @return JsonResponse
     */
    public function update($attributeCode)
    {
        return $this->save($attributeCode, 'measurement::app.messages.attribute.config_updated');
    }

    /**
     * Validate and persist the attribute measurement configuration.
     *
     * @param  string  $attributeCode
     * @param  string  $messageKey
     * @return JsonResponse
     */
    protected function save($attributeCode, $messageKey)
    {
        $data = request()->validate([
            'family_code' => ['required', 'string'],
            'unit_code'   => ['required', 'string'],
        ]);

        $attribute = $this->attributeMasterRepository->findOneByField('id', $attributeCode);

        if (! $attribute) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.attribute.not_found'),
            ], 404);
        }

        if ($attribute->type !== 'measurement') {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.attribute.not_measurement_type'),
            ], 422);
        }

        $family = $this->familyRepository->findOneWhere(['code' => $data['family_code']]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        if (! collect($family->units ?? [])->contains('code', $data['unit_code'])) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.attribute.unit_not_in_family'),
            ], 422);
        }

        try {
            $this->attributeRepository->saveAttributeMeasurement($attribute->id, [
                'family_code' => $data['family_code'],
                'unit_code'   => $data['unit_code'],
            ]);

            return response()->json([
                'success' => true,
                'message' => trans($messageKey),
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.error'),
            ], 500);
        }
    }
}
