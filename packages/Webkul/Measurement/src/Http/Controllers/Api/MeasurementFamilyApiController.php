<?php

namespace Webkul\Measurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
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

        $data = $families->map(fn ($family) => array_merge($family->toArray(), [
            'labels' => $family->labels,
            'units'  => $family->units,
        ]));

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
        $family = $this->repository->findOneWhere(['id' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
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

        if ($this->repository->count() >= MeasurementFamilyValidator::MAX_FAMILIES) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.limit_reached', [
                    'max' => MeasurementFamilyValidator::MAX_FAMILIES,
                ]),
            ], 422);
        }

        $data = $request->all();

        $unitCodes = array_column($data['units'], 'code');

        if (! in_array($data['standard_unit'], $unitCodes, true)) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.standard_unit_invalid'),
            ], 422);
        }

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
                'message' => trans('measurement::app.messages.family.created'),
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
     * Update a measurement family via API.
     *
     * @param  string  $code
     * @return JsonResponse
     */
    public function update(Request $request, $code)
    {
        $family = $this->repository->findOneWhere(['id' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        $request->validate(MeasurementFamilyValidator::apiUpdateRules($family->id), MeasurementFamilyValidator::messages());

        $data = $request->all();

        if (array_key_exists('standard_unit', $data) || array_key_exists('units', $data)) {
            $standardUnit = $data['standard_unit'] ?? $family->standard_unit;

            $unitCodes = array_key_exists('units', $data)
                ? array_column($data['units'], 'code')
                : collect($family->units ?? [])->pluck('code')->all();

            if (! in_array($standardUnit, $unitCodes, true)) {
                return response()->json([
                    'success' => false,
                    'message' => trans('measurement::app.messages.family.standard_unit_invalid'),
                ], 422);
            }
        }

        if ($conversionLocked = $this->getLockedConversionChange($family, $data)) {
            return response()->json([
                'success' => false,
                'message' => trans($conversionLocked),
            ], 422);
        }

        try {
            $this->repository->update($data, $family->id);

            return response()->json([
                'success' => true,
                'message' => trans('measurement::app.messages.family.updated'),
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
     * Delete a measurement family via API.
     *
     * @param  string  $code
     * @return JsonResponse
     */
    public function destroy($code)
    {
        $family = $this->repository->findOneWhere(['id' => $code]);

        if (! $family) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.not_found'),
            ], 404);
        }

        try {
            $this->repository->delete($family->id);

            return response()->json([
                'success' => true,
                'message' => trans('measurement::app.messages.family.deleted'),
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
     * Reject changes that would invalidate the base value already stored on
     * products: the standard unit and the unit conversion operations are
     * frozen once an attribute is configured against the family. Labels and
     * symbols stay editable.
     *
     * @return string|null translation key of the failure, or null when allowed
     */
    protected function getLockedConversionChange($family, array $data): ?string
    {
        if (! resolve(AttributeMeasurementRepository::class)->isFamilyInUse($family->code)) {
            return null;
        }

        if (
            array_key_exists('standard_unit', $data)
            && $data['standard_unit'] !== $family->standard_unit
        ) {
            return 'measurement::app.messages.family.standard_unit_locked';
        }

        if (! array_key_exists('units', $data)) {
            return null;
        }

        $existing = collect($family->units ?? [])->keyBy('code');

        foreach ($data['units'] as $unit) {
            $current = $existing->get($unit['code'] ?? null);

            if (! $current) {
                return 'measurement::app.messages.family.units_locked';
            }

            if (
                array_key_exists('convert_from_standard', $unit)
                && $unit['convert_from_standard'] != ($current['convert_from_standard'] ?? [])
            ) {
                return 'measurement::app.messages.family.units_locked';
            }
        }

        if ($existing->count() !== count($data['units'])) {
            return 'measurement::app.messages.family.units_locked';
        }

        return null;
    }
}
