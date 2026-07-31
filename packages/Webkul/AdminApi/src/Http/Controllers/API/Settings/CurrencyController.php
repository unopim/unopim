<?php

namespace Webkul\AdminApi\Http\Controllers\API\Settings;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\CurrencyDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Settings\StoreCurrencyRequest;
use Webkul\AdminApi\Http\Requests\Settings\UpdateCurrencyRequest;
use Webkul\Core\Repositories\CurrencyRepository;

class CurrencyController extends ApiController
{
    public function __construct(protected CurrencyRepository $currencyRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(CurrencyDataSource::class)->toJson();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get($code): JsonResponse
    {
        try {
            return response()->json(app(CurrencyDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Store a newly created currency.
     */
    public function store(StoreCurrencyRequest $request): JsonResponse
    {
        try {
            $this->currencyRepository->create($request->only(['code', 'symbol', 'decimal', 'status']));

            return $this->successResponse(
                trans('admin::app.settings.currencies.index.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified currency, identified by code.
     */
    public function update(UpdateCurrencyRequest $request, string $code): JsonResponse
    {
        $currency = $this->currencyRepository->findOneByField('code', $code);
        if (! $currency) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.currencies.index.not-found', ['code' => $code]));
        }

        if ($request->has('status') && ! $request->boolean('status') && $this->currencyRepository->checkCurrencyBeingUsed($currency->id)) {
            return $this->validateErrorResponse(
                ['status' => [trans('admin::app.settings.currencies.index.can-not-disable-error')]],
                trans('admin::app.settings.currencies.index.can-not-disable-error')
            );
        }

        try {
            $this->currencyRepository->update($request->only(['symbol', 'decimal', 'status']), $currency->id);

            return $this->successResponse(trans('admin::app.settings.currencies.index.update-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified currency, identified by code.
     */
    public function delete(string $code): JsonResponse
    {
        $currency = $this->currencyRepository->findOneByField('code', $code);
        if (! $currency) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.currencies.index.not-found', ['code' => $code]));
        }

        if ($this->currencyRepository->count() == 1) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.currencies.index.last-delete-error')]],
                trans('admin::app.settings.currencies.index.last-delete-error')
            );
        }

        if ($currency->isCurrencyBeingUsed()) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.currencies.index.can-not-delete-error')]],
                trans('admin::app.settings.currencies.index.can-not-delete-error')
            );
        }

        try {
            $this->currencyRepository->delete($currency->id);

            return $this->successResponse(trans('admin::app.settings.currencies.index.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
