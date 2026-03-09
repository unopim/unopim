<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class MeasurementUnitApiController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $repository
    ) {}

    public function index($familyId)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'count'   => count($family->units ?? []),
            'data'    => $family->units ?? [],
        ]);
    }

    public function store(Request $request, $familyId)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate([
            'code'   => 'required|string',
            'labels' => 'required|array',
            'symbol' => 'nullable|string',
        ]);

        $units = $family->units ?? [];

        if (collect($units)->contains('code', $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Unit code already exists',
            ], 422);
        }

        $units[] = [
            'code'   => $request->code,
            'labels' => $request->labels,
            'symbol' => $request->symbol,
        ];

        $this->repository->update(['units' => $units], $familyId);

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully',
        ]);
    }

    public function update(Request $request, $familyId, $code)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate([
            'labels' => 'nullable|array',
            'symbol' => 'required|string',
        ]);

        $units = $family->units ?? [];
        $updated = false;

        foreach ($units as &$unit) {
            if ($unit['code'] === $code) {
                $unit['labels'] = array_merge($unit['labels'] ?? [], $request->labels ?? []);
                $unit['symbol'] = $request->symbol;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found',
            ], 404);
        }

        $this->repository->update(['units' => $units], $familyId);

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully',
        ]);
    }

    public function destroy($familyId, $code)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $units = $family->units ?? [];

        $filtered = array_filter($units, fn ($u) => $u['code'] !== $code);

        $this->repository->update([
            'units' => array_values($filtered),
        ], $familyId);

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully',
        ]);
    }
}
