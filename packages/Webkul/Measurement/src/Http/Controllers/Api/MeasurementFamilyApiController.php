<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class MeasurementFamilyApiController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $repository
    ) {}

    public function index()
    {
        $data = $this->repository->all();

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:191',
            'name'          => 'required|string|max:191',
            'labels'        => 'required|array',
            'labels.en_US'  => 'required|string|max:191',
            'standard_unit' => 'required|string|max:191',
            'units'         => 'required|array|min:1',
            'units.*.code'  => 'required|string|max:191',
            'units.*.labels'=> 'required|array',
            'units.*.symbol'=> 'nullable|string|max:50',
            'symbol'        => 'nullable|string|max:50',
        ]);

        $family = $this->repository->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Measurement Family saved successfully',

        ]);
    }

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

    public function destroy($id)
    {
        $this->repository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Measurement family deleted successfully',
        ]);
    }
}
