<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\DataGrids\MeasurementFamilyDataGrid;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;
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
            return app(MeasurementFamilyDataGrid::class)->toJson();
        }
        $locales = $this->localeRepository->getActiveLocales();

        return view('measurement::measurement-families.index', compact('locales'));

    }

    /**
     * Store a newly created measurement family.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate(MeasurementFamilyValidator::storeRules());

        try {
            $familyLabels = $request->input('labels', []);
            $unitLabels = $request->input('unit_labels', []);

            $units = [
                [
                    'code'                  => $request->standard_unit_code,
                    'labels'                => $unitLabels,
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
                'labels'        => $familyLabels,
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
                'error'   => 'Something went wrong. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified measurement family.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id)
    {
        $family = $this->measurementFamilyRepository->find($id);
        $labels = $family->labels ?? [];
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

        return view('measurement::measurement-families.edit', compact('family', 'labels', 'locales', 'operationOptions', 'familyUsedInProducts'));
    }

    /**
     * Update the specified measurement family.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $family = $this->measurementFamilyRepository->find($id);

        $request->validate(MeasurementFamilyValidator::updateRules());

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

        return redirect()->back();
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

        $attributeMeasurementRepository = app(AttributeMeasurementRepository::class);

        $exists = $attributeMeasurementRepository
            ->findWhere(['family_code' => $family->code])
            ->count();

        if ($exists > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This measurement family is used in attributes, so it cannot be deleted.',
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

        if (! $ids || count($ids) == 0) {
            session()->flash('error', 'No items selected.');

            return redirect()->back();
        }

        $attributeMeasurementRepository = app(AttributeMeasurementRepository::class);

        foreach ($ids as $id) {

            $family = $this->measurementFamilyRepository->find($id);

            if (! $family) {
                continue;
            }

            $exists = $attributeMeasurementRepository
                ->findWhere(['family_code' => $family->code])
                ->count();

            if ($exists > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This measurement family is used in attributes, so it cannot be deleted.',
                ], 400);

                continue;
            }

            $this->measurementFamilyRepository->delete($id);
        }

        return response()->json([
            'success' => true,
            'message' => trans('measurement::app.messages.family.deleted'),
        ]);

        return redirect()->back();
    }
}
