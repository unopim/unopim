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
     * Store a new measurement family via API.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate(MeasurementFamilyValidator::apiStoreRules(), MeasurementFamilyValidator::messages());

        $data = $request->all();

        // Add default conversion for the standard unit
        foreach ($data['units'] as &$unit) {
            if ($unit['code'] === $data['standard_unit']) {
                $unit['convert_from_standard'] = [
                    ['operator' => 'mul', 'value' => '1'],
                ];
                break;
            }
        }

        $family = $this->repository->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Measurement Family saved successfully',

        ]);
    }

    /**
     * Update a measurement family via API.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $family = $this->repository->find($id);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $this->repository->update($request->all(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Measurement family updated successfully',
        ]);
    }

    /**
     * Delete a measurement family via API.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $this->repository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Measurement family deleted successfully',
        ]);
    }
}
