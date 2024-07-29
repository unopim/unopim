<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\DataGrids\Settings\CurrencyDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Core\Repositories\CurrencyRepository;

class CurrencyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected CurrencyRepository $currencyRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CurrencyDataGrid::class)->toJson();
        }

        return view('admin::settings.currencies.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        $messages = [
            'code.required' => 'The code field is required.',
            'code.unique'   => 'The code must be unique.',
        ];

        $this->validate(request(), [
            'code' => 'required|min:3|max:3|unique:currencies,code',
        ], $messages);

        $this->currencyRepository->create(request()->only([
            'code',
            'symbol',
            'decimal',
            'status',
        ]));

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.create-success'),
        ]);
    }

    /**
     * Currency Details
     */
    public function edit(int $id): JsonResponse
    {
        $currency = $this->currencyRepository->findOrFail($id);

        return new JsonResponse($currency);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(): JsonResponse
    {
        $id = request('id');

        $this->validate(request(), [
            'code'      => ['required', 'unique:currencies,code,'.$id, new \Webkul\Core\Rules\Code],
            'status'    => 'boolean',
        ]);

        if (! request()->status && $this->currencyRepository->checkCurrencyBeingUsed($id)) {
            return new JsonResponse([
                'errors' => [
                    'status' => trans('admin::app.settings.currencies.index.can-not-disable-error'),
                ],
            ], 422);
        }

        $this->currencyRepository->update(request()->only([
            'symbol',
            'decimal',
            'status',
        ]), $id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.update-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $currency = $this->currencyRepository->findOrFail($id);

        if ($currency->count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.last-delete-error'),
            ], 400);
        }

        if ($currency->isCurrencyBeingUsed()) {
            return response()->json([
                'message' => trans('admin::app.settings.currencies.index.can-not-delete-error'),
            ], 400);
        }

        try {
            $this->currencyRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.delete-failed'),
        ], 500);
    }

    /**
     * Mass Delete currencies
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $currencyIds = $massDestroyRequest->input('indices');
        $delete = false;

        foreach ($currencyIds as $currencyId) {
            $currency = $this->currencyRepository->find($currencyId);

            if (! $currency) {
                continue;
            }

            if ($currency->count() == 1) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.currencies.index.last-delete-error'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($currency->isCurrencyBeingUsed()) {
                continue;
            }

            try {
                $this->currencyRepository->delete($currencyId);

                $delete = true;
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if (! $delete) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.cannot-delete'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.delete-success'),
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Mass update currencies status through datagrid
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $currencyIds = $massUpdateRequest->input('indices');

        $value = $massUpdateRequest->input('value');

        foreach ($currencyIds as $currencyId) {
            $currency = $this->currencyRepository->find($currencyId);

            if (! $currency) {
                continue;
            }

            if ($currency->isCurrencyBeingUsed() && $value === 0) {
                continue;
            }

            try {
                $currency->status = $value;

                $currency->save();
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.update-success'),
        ]);
    }
}
