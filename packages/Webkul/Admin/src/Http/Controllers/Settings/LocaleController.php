<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\DataGrids\Settings\LocalesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(LocalesDataGrid::class)->toJson();
        }

        return view('admin::settings.locales.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'code'        => ['required', 'unique:locales,code', new \Webkul\Core\Rules\Code],
        ]);

        $this->localeRepository->create(request()->only([
            'code',
            'status',
        ]));

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.create-success'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): JsonResponse
    {
        $locale = $this->localeRepository->findOrFail($id);

        return new JsonResponse([
            'data' => [
                ...$locale->toArray(),
                'status' => (bool) $locale->status,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'status' => 'boolean',
        ]);

        if (! request()->status && $this->localeRepository->checkLocaleBeingUsed(request()->id)) {
            return new JsonResponse([
                'errors' => [
                    'status' => trans('admin::app.settings.locales.index.can-not-disable-error'),
                ],
            ], 422);
        }

        $this->localeRepository->update(request()->only([
            'status',
        ]), request()->id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.update-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $locale = $this->localeRepository->findOrFail($id);

        if ($locale->count() == 1) {
            return response()->json([
                'message' => trans('admin::app.settings.locales.index.last-delete-error'),
            ], 400);
        }

        if ($locale->isLocaleBeingUsed()) {
            return response()->json([
                'message' => trans('admin::app.settings.locales.index.can-not-delete-error'),
            ], 400);
        }

        try {
            $locale->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.locales.index.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('admin::app.settings.locales.index.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete locales from the locale datagrid
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $localeIds = $massDestroyRequest->input('indices');

        foreach ($localeIds as $localeId) {
            $locale = $this->localeRepository->find($localeId);

            if (! $locale) {
                continue;
            }

            if ($locale->count() == 1) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.locales.index.last-delete-error'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($locale->isLocaleBeingUsed()) {
                continue;
            }

            try {
                $this->localeRepository->delete($localeId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.delete-success'),
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Mass update locales status from the locale datagrid
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $localeIds = $massUpdateRequest->input('indices');

        $value = $massUpdateRequest->input('value');

        foreach ($localeIds as $localeId) {
            $locale = $this->localeRepository->find($localeId);

            if (! $locale) {
                continue;
            }

            if ($locale->isLocaleBeingUsed() && $value === 0) {
                continue;
            }

            try {
                $locale->status = $value;

                $locale->save();
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.update-success'),
        ]);
    }
}
