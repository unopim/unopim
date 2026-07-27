<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\DataGrids\MeasurementFamilyDataGrid;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementFamilyValidator;

class MeasurementFamilyController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Display a listing of measurement families.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return resolve(MeasurementFamilyDataGrid::class)->toJson();
        }
        $locales = $this->localeRepository->getActiveLocales();

        return view('measurement::measurement-families.index', ['locales' => $locales]);

    }

    /**
     * Store a newly created measurement family.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate(MeasurementFamilyValidator::storeRules(), MeasurementFamilyValidator::messages());

        if ($this->measurementFamilyRepository->count() >= MeasurementFamilyValidator::MAX_FAMILIES) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.limit_reached', [
                    'max' => MeasurementFamilyValidator::MAX_FAMILIES,
                ]),
            ], 422);
        }

        try {
            $units = [
                [
                    'code'                  => $request->standard_unit_code,
                    'labels'                => [],
                    'symbol'                => $request->symbol,
                    'convert_from_standard' => [
                        [
                            'value'    => '1',
                            'operator' => 'mul',
                        ],
                    ],
                ],
            ];

            $data = [
                'code'          => $request->code,
                'name'          => $request->code,
                'labels'        => [],
                'standard_unit' => $request->standard_unit_code,
                'units'         => $units,
                'symbol'        => $request->symbol,
            ];

            $family = $this->measurementFamilyRepository->create($data);

            session()->flash(
                'success',
                trans('measurement::app.messages.family.created')
            );

            return response()->json([
                'data' => [
                    'redirect_url' => route(
                        'admin.measurement.families.edit',
                        $family->id
                    ),
                ],
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error'   => trans('measurement::app.messages.family.error'),
                'message' => trans('measurement::app.messages.family.error'),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified measurement family.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id): Factory|View
    {
        $family = $this->measurementFamilyRepository->find($id);
        $labels = $family->labels ?? [];
        $locales = $this->localeRepository->getActiveLocales();

        $operationOptions = [
            ['value' => 'mul', 'label' => trans('measurement::app.operators.mul')],
            ['value' => 'div', 'label' => trans('measurement::app.operators.div')],
            ['value' => 'add', 'label' => trans('measurement::app.operators.add')],
            ['value' => 'sub', 'label' => trans('measurement::app.operators.sub')],
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

        return view('measurement::measurement-families.edit', ['family' => $family, 'labels' => $labels, 'locales' => $locales, 'operationOptions' => $operationOptions, 'familyUsedInProducts' => $familyUsedInProducts]);
    }

    /**
     * Update the specified measurement family.
     *
     * @param  int  $id
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $family = $this->measurementFamilyRepository->find($id);

        $request->validate(MeasurementFamilyValidator::updateRules(), MeasurementFamilyValidator::messages());

        $oldLabels = $family->labels ?? [];
        $newLabels = $request->input('labels', []);
        $mergedLabels = array_merge($oldLabels, $newLabels);

        $data = [
            'labels' => $mergedLabels,
        ];

        $this->measurementFamilyRepository->update($data, $id);

        session()->flash(
            'success',
            trans('measurement::app.messages.family.updated')
        );

        return back();
    }

    /**
     * Remove the specified measurement family.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $family = $this->measurementFamilyRepository->findOrFail($id);

        $attributeMeasurementRepository = resolve(AttributeMeasurementRepository::class);

        $exists = $attributeMeasurementRepository
            ->findWhere(['family_code' => $family->code])
            ->count();

        if ($exists > 0) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.family.in_use'),
            ], 400);
        }

        $this->measurementFamilyRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => trans('measurement::app.messages.family.deleted'),
        ]);
    }

    /**
     * Remove multiple measurement families.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function massDelete()
    {
        $ids = request()->input('indices');

        if (! $ids || count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => trans('measurement::app.messages.unit.no_items_selected'),
            ], 400);
        }

        $attributeMeasurementRepository = resolve(AttributeMeasurementRepository::class);

        $failedFamilies = [];

        foreach ($ids as $id) {
            $family = $this->measurementFamilyRepository->find($id);

            if (! $family) {
                continue;
            }

            $exists = $attributeMeasurementRepository
                ->findWhere(['family_code' => $family->code])
                ->count();

            if ($exists > 0) {
                $failedFamilies[] = $family->code;

                continue;
            }

            $this->measurementFamilyRepository->delete($id);
        }

        if ($failedFamilies !== []) {
            return response()->json([
                'success'         => false,
                'message'         => trans('measurement::app.messages.family.partially_deleted'),
                'failed_families' => $failedFamilies,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => trans('measurement::app.messages.family.deleted'),
        ]);
    }
}
