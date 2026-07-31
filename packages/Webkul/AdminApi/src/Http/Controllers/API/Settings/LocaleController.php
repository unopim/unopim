<?php

namespace Webkul\AdminApi\Http\Controllers\API\Settings;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\LocaleDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Settings\StoreLocaleRequest;
use Webkul\AdminApi\Http\Requests\Settings\UpdateLocaleRequest;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleController extends ApiController
{
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(LocaleDataSource::class)->toJson();
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
            return response()->json(app(LocaleDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Store a newly created locale.
     */
    public function store(StoreLocaleRequest $request): JsonResponse
    {
        try {
            $this->localeRepository->create($request->only(['code', 'status']));

            return $this->successResponse(
                trans('admin::app.settings.locales.index.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified locale, identified by code.
     */
    public function update(UpdateLocaleRequest $request, string $code): JsonResponse
    {
        $locale = $this->localeRepository->findOneByField('code', $code);
        if (! $locale) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.locales.index.not-found', ['code' => $code]));
        }

        if ($request->has('status') && ! $request->boolean('status') && $this->localeRepository->checkLocaleBeingUsed($locale->id)) {
            return $this->validateErrorResponse(
                ['status' => [trans('admin::app.settings.locales.index.can-not-disable-error')]],
                trans('admin::app.settings.locales.index.can-not-disable-error')
            );
        }

        try {
            $this->localeRepository->update($request->only(['status']), $locale->id);

            return $this->successResponse(trans('admin::app.settings.locales.index.update-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified locale, identified by code.
     */
    public function delete(string $code): JsonResponse
    {
        $locale = $this->localeRepository->findOneByField('code', $code);
        if (! $locale) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.locales.index.not-found', ['code' => $code]));
        }

        if ($this->localeRepository->count() == 1) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.locales.index.last-delete-error')]],
                trans('admin::app.settings.locales.index.last-delete-error')
            );
        }

        if ($locale->isLocaleBeingUsed()) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.locales.index.can-not-delete-error')]],
                trans('admin::app.settings.locales.index.can-not-delete-error')
            );
        }

        try {
            $this->localeRepository->delete($locale->id);

            return $this->successResponse(trans('admin::app.settings.locales.index.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
