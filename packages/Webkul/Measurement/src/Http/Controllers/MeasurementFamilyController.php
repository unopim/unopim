<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\DataGrids\MeasurementFamilyDataGrid;
use Webkul\Measurement\DataGrids\UnitDataGrid;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class MeasurementFamilyController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected LocaleRepository $localeRepository
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return app(MeasurementFamilyDataGrid::class)->toJson();
        }
        $locales = $this->localeRepository->getActiveLocales();

        return view('measurement::measurement-families.index', compact('locales'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'code'               => 'required|string|max:191',
            'standard_unit_code' => 'required|string|max:191',
            'symbol'             => 'nullable|string|max:50',
            'labels'             => 'required|array',
        ]);

        $labels = $request->input('labels', []);

        $units = [
            [
                'code'   => $request->standard_unit_code,
                'labels' => $labels,
                'symbol' => $request->symbol,
            ],
        ];

        $data = [
            'code'          => $request->code,
            'name'          => reset($labels),
            'labels'        => $labels,
            'standard_unit' => $request->standard_unit_code,
            'units'         => $units,
            'symbol'        => $request->symbol,
        ];

        $family = $this->measurementFamilyRepository->create($data);

        return response()->json([
            'data' => [
                'redirect_url' => route(
                    'admin.measurement.families.edit',
                    $family->id
                ),
            ],
        ]);
    }

    public function edit($id)
    {
        $family = $this->measurementFamilyRepository->find($id);
        $labels = $family->labels ?? [];
        $locales = $this->localeRepository->getActiveLocales();

        return view('measurement::measurement-families.edit', compact('family', 'labels', 'locales'));
    }

    public function update(Request $request, $id)
    {
        $family = $this->measurementFamilyRepository->find($id);

        $request->validate([
            'labels'   => 'nullable|array',
            'labels.*' => 'nullable|string',
        ]);

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

    public function destroy($id)
    {
        $this->measurementFamilyRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => trans('measurement::app.messages.family.deleted'),
        ]);

    }

    public function massDelete()
    {
        $ids = request()->input('indices');

        if (! $ids || count($ids) == 0) {
            session()->flash('error', 'No items selected.');

            return redirect()->back();
        }

        foreach ($ids as $id) {
            $this->measurementFamilyRepository->delete($id);
        }

        session()->flash(
            'success',
            trans('measurement::app.messages.family.mass_deleted')
        );

        return redirect()->back();
    }

}
