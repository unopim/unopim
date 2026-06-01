<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\DataGrids\UnitDataGrid;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

class MeasurementUnitsController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Display units for a measurement family (DataGrid or view).
     *
     * @param  int  $id
     * @return JsonResponse|View
     */
    public function units($id)
    {
        if (request()->ajax()) {

            $grid = app(UnitDataGrid::class);
            $grid->setFamilyId($id);

            return $grid->toJson();
        }

        $family = $this->measurementFamilyRepository->find($id);
        $locales = $this->localeRepository->getActiveLocales();

        $operationOptions = [
            ['value' => 'mul', 'label' => 'Multiply'],
            ['value' => 'div', 'label' => 'Divide'],
            ['value' => 'add', 'label' => 'Add'],
            ['value' => 'sub', 'label' => 'Subtract'],
        ];

        $familyUsedInProducts = false;
        if (isset($family->units)) {
            foreach ($family->units as $unitData) {
                if (isset($unitData['code']) && $this->attributeMeasurementRepository->findWhere(['unit_code' => $unitData['code']])->count() > 0) {
                    $familyUsedInProducts = true;
                    break;
                }
            }
        }

        return view('measurement::measurement-families.edit', compact('family', 'locales', 'operationOptions', 'familyUsedInProducts'));
    }

    /**
     * Store a new unit for the given family.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function storeUnit($id)
    {
        $family = $this->measurementFamilyRepository->find($id);

        if (! $family) {
            return response()->json([
                'message' => trans('measurement::app.messages.unit.not_found'),
            ], 404);
        }

        request()->validate(MeasurementUnitValidator::storeRules(), MeasurementUnitValidator::messages());

        $units = $family->units ?? [];

        if (collect($units)->contains('code', request('code'))) {
            return response()->json([
                'message' => trans('measurement::app.messages.unit.already_exists'),
            ], 422);
        }

        $conversionOperators = request('convert_from_standard', []);
        $conversionValues = request('convert_value', []);

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

        $newUnit = [
            'code'                  => request('code'),
            'labels'                => request('labels'),
            'symbol'                => request('symbol'),
            'convert_from_standard' => array_slice($conversionRows, 0, 4),
        ];

        $units[] = $newUnit;

        $this->measurementFamilyRepository->update([
            'units' => $units,
        ], $id);

        return response()->json([
            'data' => [
                'redirect_url' => route(
                    'admin.measurement.families.units.edit',
                    ['familyId' => $id, 'code' => $newUnit['code']]
                ),
            ],
        ]);
    }

    /**
     * Return JSON data for editing a single unit.
     */
    public function editUnit(int $familyId, string $code): JsonResponse
    {
        $family = $this->measurementFamilyRepository->findOrFail($familyId);

        $unit = collect($family->units)->firstWhere('code', $code);

        if (! $unit) {
            abort(
                404,
                trans('measurement::app.messages.unit.units_not_found')
            );

        }

        $isStandard = $family->standard_unit === $code;

        $isUsedInProducts = $this->attributeMeasurementRepository->findWhere(['unit_code' => $code])->count() > 0;

        return new JsonResponse([
            'data' => [
                ...$unit,
                'is_used_in_products' => $isUsedInProducts,

                'is_standard'           => $isStandard,
                'status'                => isset($unit['status']) ? (bool) $unit['status'] : true,
                'labels'                => $unit['labels'] ?? [],
                'precision'             => $unit['precision'] ?? null,
                'symbol'                => $unit['symbol'] ?? null,
                'convert_from_standard' => is_array($unit['convert_from_standard'] ?? null)
                    ? $unit['convert_from_standard']
                    : [
                        [
                            'operator' => $unit['convert_from_standard'] ?? 'mul',
                            'value'    => $unit['convert_value'] ?? null,
                        ],
                    ],
                'convert_value' => $unit['convert_value'] ?? null,
                'family_id'     => $familyId,
            ],
        ]);
    }

    /**
     * Update an existing unit on a measurement family.
     *
     * @param  int  $familyId
     * @param  string  $code
     * @return JsonResponse|RedirectResponse
     */
    public function updateUnit($familyId, $code)
    {
        $family = $this->measurementFamilyRepository->find($familyId);

        if (! $family) {
            abort(
                404,
                trans('measurement::app.messages.family.not_found')
            );
        }

        request()->validate(
            MeasurementUnitValidator::updateRules(),
            MeasurementUnitValidator::messages()
        );

        $units = $family->units ?? [];

        $newLabels = request('labels', []);

        foreach ($units as &$unit) {

            if ($unit['code'] !== $code) {
                continue;
            }

            // Update labels
            $unit['labels'] = array_merge(
                $unit['labels'] ?? [],
                $newLabels
            );

            // Update symbol
            $unit['symbol'] = request('symbol');

            $conversionOperators = request('convert_from_standard');
            $conversionValues = request('convert_value');

            /**
             * Only update conversion data when fields are submitted.
             * Otherwise preserve existing values.
             */
            if (
                request()->has('convert_from_standard')
                || request()->has('convert_value')
            ) {
                $conversionRows = [];

                foreach ((array) $conversionOperators as $index => $operator) {
                    $conversionRows[] = [
                        'operator' => $operator ?: 'mul',
                        'value'    => isset($conversionValues[$index])
                            ? (string) $conversionValues[$index]
                            : '1',
                    ];
                }

                // Standard unit fallback
                if (empty($conversionRows)) {
                    $conversionRows[] = [
                        'operator' => 'mul',
                        'value'    => '1',
                    ];
                }

                $unit['convert_from_standard'] = array_slice(
                    $conversionRows,
                    0,
                    4
                );
            }

            // Ensure standard unit always has valid conversion
            if (empty($unit['convert_from_standard'])) {
                $unit['convert_from_standard'] = [
                    [
                        'operator' => 'mul',
                        'value'    => '1',
                    ],
                ];
            }

            break;
        }

        $this->measurementFamilyRepository->update([
            'units' => $units,
        ], $familyId);

        if (request()->ajax()) {
            return response()->json([
                'data' => [
                    'redirect_url' => route(
                        'admin.measurement.families.units.edit',
                        [
                            'familyId' => $familyId,
                            'code'     => $code,
                        ]
                    ),
                ],
            ]);
        }

        return redirect()->back();
    }

    /**
     * Delete a unit from a measurement family.
     *
     * @param  int  $familyId
     * @param  string  $code
     * @return JsonResponse
     */
    public function deleteUnit($familyId, $code)
    {
        $family = $this->measurementFamilyRepository->findOrFail($familyId);

        $attributeMeasurementRepository = app(AttributeMeasurementRepository::class);

        $exists = $attributeMeasurementRepository
            ->findWhere(['unit_code' => $code])
            ->count();

        if ($exists > 0) {
            return response()->json([
                'status'  => false,
                'message' => 'This unit is used in attributes, so it cannot be deleted.',
            ], 400);
        }

        $units = $family->units ?? [];

        $updatedUnits = array_filter($units, function ($unit) use ($code) {
            return isset($unit['code']) && $unit['code'] !== $code;
        });

        $this->measurementFamilyRepository->update([
            'units' => array_values($updatedUnits),
        ], $familyId);

        return response()->json([
            'status'  => true,
            'message' => trans('measurement::app.messages.unit.deleted'),
        ]);
    }
}
