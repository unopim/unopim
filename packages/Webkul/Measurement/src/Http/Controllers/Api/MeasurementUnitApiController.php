<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

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

    /**
     * Store a new unit for the given measurement family (API).
     *
     * @param  int  $familyId
     * @return JsonResponse
     */
    public function store(Request $request, $familyId)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate(MeasurementUnitValidator::storeRules());

        $units = $family->units ?? [];

        if (collect($units)->contains('code', $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Unit code already exists',
            ], 422);
        }

        $conversionOperators = $request->input('convert_from_standard', []);
        $conversionValues = $request->input('convert_value', []);

        $conversionRows = [];
        foreach ((array) $conversionOperators as $index => $operator) {
            $conversionRows[] = [
                'operator' => $operator ?: 'mul',
                'value'    => isset($conversionValues[$index]) ? (string) $conversionValues[$index] : null,
            ];
        }

        if (count($conversionRows) === 0) {
            $conversionRows[] = [
                'operator' => 'mul',
                'value'    => null,
            ];
        }

        $units[] = [
            'code'                  => $request->code,
            'labels'                => $request->labels,
            'symbol'                => $request->symbol,
            'convert_from_standard' => array_slice($conversionRows, 0, 4),
        ];

        $this->repository->update(['units' => $units], $familyId);

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully',
        ]);
    }

    /**
     * Update a unit for the given measurement family (API).
     *
     * @param  int  $familyId
     * @param  string  $code
     * @return JsonResponse
     */
    public function update(Request $request, $familyId, $code)
    {
        $family = $this->repository->find($familyId);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate(MeasurementUnitValidator::updateRules());

        $units = $family->units ?? [];
        $updated = false;

        foreach ($units as &$unit) {
            if ($unit['code'] === $code) {
                $unit['labels'] = array_merge($unit['labels'] ?? [], $request->labels ?? []);
                $unit['symbol'] = $request->symbol;

                if ($code !== $family->standard_unit) {
                    $conversionOperators = $request->input('convert_from_standard', []);
                    $conversionValues = $request->input('convert_value', []);

                    $conversionRows = [];
                    foreach ((array) $conversionOperators as $index => $operator) {
                        $conversionRows[] = [
                            'operator' => $operator ?: 'mul',
                            'value'    => isset($conversionValues[$index]) ? (string) $conversionValues[$index] : null,
                        ];
                    }

                    if (count($conversionRows) === 0) {
                        $conversionRows[] = [
                            'operator' => 'mul',
                            'value'    => null,
                        ];
                    }

                    $unit['convert_from_standard'] = array_slice($conversionRows, 0, 4);
                }

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

    /**
     * Delete a unit for the given measurement family (API).
     *
     * @param  int  $familyId
     * @param  string  $code
     * @return JsonResponse
     */
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
