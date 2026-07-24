<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

class MeasurementUnitApiController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $repository
    ) {}

    /**
     * List the units of a measurement family.
     */
    public function index($familyCode)
    {
        $family = $this->repository->findOneWhere(['id' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
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
        $family = $this->repository->findOneWhere(['id' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        $unit = collect($family->units ?? [])->firstWhere('code', $code);

        if (! $unit) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.units_not_found'),
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
        $family = $this->repository->findOneWhere(['id' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        $request->validate(MeasurementUnitValidator::storeRules(), MeasurementUnitValidator::messages());

        $units = $family->units ?? [];

        if (collect($units)->contains('code', $request->code)) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.already_exists'),
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
                'message' => trans('measurement::app.messages.unit.created'),
            ], 201);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.error'),
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
        $family = $this->repository->findOneWhere(['id' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        $request->validate(MeasurementUnitValidator::updateRules());

        $units = $family->units ?? [];
        $updated = false;

        foreach ($units as &$unit) {
            if ($unit['code'] === $code) {
                $unit['labels'] = array_merge($unit['labels'] ?? [], $request->labels ?? []);
                $unit['symbol'] = $request->symbol;

                if (
                    $code !== $family->standard_unit
                    && ! resolve(AttributeMeasurementRepository::class)->isFamilyInUse($family->code)
                ) {
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
                'message' => trans('measurement::app.messages.unit.units_not_found'),
            ], 404);
        }

        try {
            $this->repository->update(['units' => $units], $family->id);

            return response()->json([
                'success' => true,
                'message' => trans('measurement::app.messages.unit.updated'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.error'),
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
        $family = $this->repository->findOneWhere(['id' => $familyCode]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        $units = $family->units ?? [];

        if (! collect($units)->contains('code', $code)) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.units_not_found'),
            ], 404);
        }

        if ($code === $family->standard_unit) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.standard_cannot_delete'),
            ], 422);
        }

        if (resolve(AttributeMeasurementRepository::class)->findWhere(['unit_code' => $code])->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.in_use'),
            ], 422);
        }

        $filtered = array_filter($units, fn (array $u): bool => $u['code'] !== $code);

        try {
            $this->repository->update([
                'units' => array_values($filtered),
            ], $family->id);

            return response()->json([
                'success' => true,
                'message' => trans('measurement::app.messages.unit.deleted'),
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
