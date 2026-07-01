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

    public function index($familyCode)
    {
        $family = $this->repository->findOneWhere(['code' => $familyCode]);

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
     * Show a single unit of the given measurement family (API).
     *
     * @param  string  $familyCode
     * @param  string  $code
     * @return JsonResponse
     */
    public function show($familyCode, $code)
    {
        $family = $this->repository->findOneWhere(['code' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $unit = collect($family->units ?? [])->firstWhere('code', $code);

        if (! $unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $unit,
        ]);
    }

    /**
     * Store a new unit for the given measurement family (API).
     *
     * @param  string  $familyCode
     * @return JsonResponse
     */
    public function store(Request $request, $familyCode)
    {
        $family = $this->repository->findOneWhere(['code' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $request->validate(MeasurementUnitValidator::storeRules(), MeasurementUnitValidator::messages());

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
            'convert_from_standard' => array_slice($conversionRows, 0, 5),
        ];

        try {
            $this->repository->update(['units' => $units], $family->id);

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a unit for the given measurement family (API).
     *
     * @param  string  $familyCode
     * @param  string  $code
     * @return JsonResponse
     */
    public function update(Request $request, $familyCode, $code)
    {
        $family = $this->repository->findOneWhere(['code' => $familyCode]);

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

                    $unit['convert_from_standard'] = array_slice($conversionRows, 0, 5);
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

        try {
            $this->repository->update(['units' => $units], $family->id);

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a unit for the given measurement family (API).
     *
     * @param  string  $familyCode
     * @param  string  $code
     * @return JsonResponse
     */
    public function destroy($familyCode, $code)
    {
        $family = $this->repository->findOneWhere(['code' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement family not found',
            ], 404);
        }

        $units = $family->units ?? [];

        if (! collect($units)->contains('code', $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found',
            ], 404);
        }

        if ($code === $family->standard_unit) {
            return response()->json([
                'success' => false,
                'message' => 'The standard unit cannot be deleted',
            ], 422);
        }

        $filtered = array_filter($units, fn ($u) => $u['code'] !== $code);

        try {
            $this->repository->update([
                'units' => array_values($filtered),
            ], $family->id);

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
