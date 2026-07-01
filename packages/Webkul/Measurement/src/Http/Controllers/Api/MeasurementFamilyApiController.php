<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementFamilyValidator;

class MeasurementFamilyApiController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $repository
    ) {}

    /**
     * List all measurement families with their labels and units.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $families = $this->repository->all();

        $data = $families->map(function ($family) {
            return array_merge($family->toArray(), [
                'labels' => $family->labels,
                'units'  => $family->units,
            ]);
        });

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    /**
     * Show a single measurement family with its labels and units.
     *
     * @param  string  $code
     * @return JsonResponse
     */
    public function show($code)
    {
        $family = $this->repository->findOneWhere(['code' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => array_merge($family->toArray(), [
                'labels' => $family->labels,
                'units'  => $family->units,
            ]),
        ]);
    }

    /**
     * Store a new measurement family via API.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate(MeasurementFamilyValidator::apiStoreRules(), MeasurementFamilyValidator::messages());

        $data = $request->all();

        $unitCodes = array_column($data['units'], 'code');

        if (! in_array($data['standard_unit'], $unitCodes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The standard unit must be one of the provided units.',
            ], 422);
        }

        // Add default conversion for the standard unit
        foreach ($data['units'] as &$unit) {
            if ($unit['code'] === $data['standard_unit']) {
                $unit['convert_from_standard'] = [
                    ['operator' => 'mul', 'value' => '1'],
                ];
                break;
            }
        }

        try {
            $this->repository->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Measurement Family saved successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a measurement family via API.
     *
     * @param  string  $code
     * @return JsonResponse
     */
    public function update(Request $request, $code)
    {
        $family = $this->repository->findOneWhere(['code' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate(MeasurementFamilyValidator::apiUpdateRules($family->id), MeasurementFamilyValidator::messages());

        $data = $request->all();

        // When the units and/or the standard unit are being changed, the standard
        // unit must still be one of the family's units, otherwise conversions break.
        if (array_key_exists('standard_unit', $data) || array_key_exists('units', $data)) {
            $standardUnit = $data['standard_unit'] ?? $family->standard_unit;

            $unitCodes = array_key_exists('units', $data)
                ? array_column($data['units'], 'code')
                : collect($family->units ?? [])->pluck('code')->all();

            if (! in_array($standardUnit, $unitCodes, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The standard unit must be one of the provided units.',
                ], 422);
            }
        }

        try {
            $this->repository->update($data, $family->id);

            return response()->json([
                'success' => true,
                'message' => 'Measurement family updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a measurement family via API.
     *
     * @param  string  $code
     * @return JsonResponse
     */
    public function destroy($code)
    {
        $family = $this->repository->findOneWhere(['code' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        try {
            $this->repository->delete($family->id);

            return response()->json([
                'success' => true,
                'message' => 'Measurement family deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
