<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\DataGrids\MeasurementFamilyDataGrid;
use Webkul\Measurement\DataGrids\UnitDataGrid;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class MeasurementUnitsController extends Controller
{
    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected LocaleRepository $localeRepository
    ) {}



    public function units($id)
    {
        if (request()->ajax()) {

            $grid = app(UnitDataGrid::class);
            $grid->setFamilyId($id);

            return $grid->toJson();
        }

        $family = $this->measurementFamilyRepository->find($id);
        $locales = $this->localeRepository->getActiveLocales();

        return view('measurement::measurement-families.edit', compact('family', 'locales'));
    }

    public function storeUnit($id)
    {
        $family = $this->measurementFamilyRepository->find($id);

        if (! $family) {
            return response()->json([
                'message' => trans('measurement::app.messages.unit.not_found'),
            ], 404);
        }

        request()->validate([
            'code'        => 'required|string',
            'labels'      => 'required|array',
            'labels.*'    => 'nullable|string',
            'symbol'      => 'nullable|string',
        ]);

        $units = $family->units ?? [];

        if (collect($units)->contains('code', request('code'))) {
            return response()->json([
                'message' => trans('measurement::app.messages.unit.already_exists'),
            ], 422);
        }

        $newUnit = [
            'code'   => request('code'),
            'labels' => request('labels'),
            'symbol' => request('symbol'),
        ];

        $units[] = $newUnit;

        $this->measurementFamilyRepository->update([
            'units' => $units,
        ], $id);
    }

    public function editUnit(int $familyId, string $code): JsonResponse
    {
        $family = $this->measurementFamilyRepository->findOrFail($familyId);

        $unit = collect($family->units)->firstWhere('code', $code);

        if (! $unit) {
            abort(
                404,
                trans('measurement::app.messages.unit.not_foundd')
            );

        }

        return new JsonResponse([
            'data' => [
                ...$unit,

                'status'     => isset($unit['status']) ? (bool) $unit['status'] : true,
                'labels'     => $unit['labels'] ?? [],
                'precision'  => $unit['precision'] ?? null,
                'symbol'     => $unit['symbol'] ?? null,
                'family_id'  => $familyId,
            ],
        ]);
    }

    public function updateUnit($familyId, $code)
    {
        $family = $this->measurementFamilyRepository->find($familyId);

        if (! $family) {
            abort(
                404,
                trans('measurement::app.messages.family.not_found')
            );

        }

        request()->validate([
            'symbol'      => 'required|string',
            'labels'      => 'nullable|array',
            'labels.*'    => 'nullable|string',
        ]);

        $units = $family->units ?? [];

        $newLabels = request('labels', []);
        foreach ($units as &$unit) {

            if ($unit['code'] === $code) {

                $unit['labels'] = array_merge(
                    $unit['labels'] ?? [],
                    $newLabels
                );

                $unit['symbol'] = request('symbol');

                break;
            }
        }

        $this->measurementFamilyRepository->update([
            'units' => $units,
        ], $familyId);
    }

    public function deleteUnit($familyId, $code)
    {
        $family = $this->measurementFamilyRepository->findOrFail($familyId);

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

    public function unitmassDelete()
    {
        $ids = request()->input('indices');

        if (! $ids || count($ids) == 0) {
            session()->flash(
            'error',
            trans('measurement::app.messages.unit.no_items_selected')
        );

            return redirect()->back();
        }

        foreach ($ids as $id) {
            $this->measurementFamilyRepository->delete($id);
        }

        session()->flash(
            'success',
            trans('measurement::app.messages.unit.mass_deleted')
        );

        return redirect()->back();
    }
}
