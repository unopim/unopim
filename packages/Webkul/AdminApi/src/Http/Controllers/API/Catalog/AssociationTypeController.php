<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AssociationTypeDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAssociationTypeFieldRequest;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAssociationTypeRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAssociationTypeFieldRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAssociationTypeRequest;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;
use Webkul\Product\Repositories\AssociationTypeRepository;

class AssociationTypeController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AssociationTypeRepository $associationTypeRepository,
        protected AssociationTypeFieldRepository $associationTypeFieldRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(AssociationTypeDataSource::class)->toJson();
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get(string $code): JsonResponse
    {
        try {
            return response()->json(app(AssociationTypeDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssociationTypeRequest $request): JsonResponse
    {
        $requestData = $this->setLabels($request->all());

        $requestData['status'] = $requestData['status'] ?? true;
        $requestData['is_user_defined'] = 1;
        $requestData['position'] = ((int) $this->associationTypeRepository->max('position')) + 1;

        try {
            Event::dispatch('catalog.association_type.create.before');

            $associationType = $this->associationTypeRepository->create($requestData);

            Event::dispatch('catalog.association_type.create.after', $associationType);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssociationTypeRequest $request, string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $immutable = array_intersect(['code', 'is_user_defined'], array_keys(request()->all()));

        if (! empty($immutable)) {
            return $this->validateErrorResponse([
                'immutable' => [trans('admin::app.catalog.association_types.immutable-fields', ['fields' => implode(', ', $immutable)])],
            ]);
        }

        $requestData = $this->setLabels(request()->except(['code', 'is_user_defined']));

        try {
            Event::dispatch('catalog.association_type.update.before');

            $associationType = $this->associationTypeRepository->update($requestData, $associationType->id);

            Event::dispatch('catalog.association_type.update.after', $associationType);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Partially update the specified resource.
     */
    public function partialUpdate(UpdateAssociationTypeRequest $request, string $code): JsonResponse
    {
        return $this->update($request, $code);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        if (! $associationType->is_user_defined) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.catalog.association_types.user-define-error')]],
                trans('admin::app.catalog.association_types.user-define-error')
            );
        }

        try {
            Event::dispatch('catalog.association_type.delete.before', $associationType->id);

            $this->associationTypeRepository->delete($associationType->id);

            Event::dispatch('catalog.association_type.delete.after', $associationType->id);

            return $this->successResponse(trans('admin::app.catalog.association_types.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display the custom fields defined on the association type.
     */
    public function getFields(string $code): JsonResponse
    {
        try {
            return response()->json(app(AssociationTypeDataSource::class)->getFieldsByTypeCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created field on the association type.
     */
    public function storeField(StoreAssociationTypeFieldRequest $request, string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $requestData = $this->setLabels($request->all());
        $requestData['status'] = $requestData['status'] ?? true;
        $requestData['is_required'] = $requestData['is_required'] ?? false;
        $requestData['is_unique'] = $requestData['is_unique'] ?? false;
        $requestData['value_per_locale'] = $requestData['value_per_locale'] ?? false;
        $requestData['validation'] = $requestData['validation'] ?? null;
        $requestData['association_type_id'] = $associationType->id;

        try {
            $this->associationTypeFieldRepository->create($requestData);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.fields.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified field on the association type.
     */
    public function updateField(UpdateAssociationTypeFieldRequest $request, string $code, string $fieldCode): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $field = $this->associationTypeFieldRepository->findOneWhere([
            'code'                => $fieldCode,
            'association_type_id' => $associationType->id,
        ]);

        if (! $field) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.fields.not-found', ['code' => $fieldCode]));
        }

        $immutable = array_intersect(['code', 'type'], array_keys(request()->all()));

        if (! empty($immutable)) {
            return $this->validateErrorResponse([
                'immutable' => [trans('admin::app.catalog.association_types.immutable-fields', ['fields' => implode(', ', $immutable)])],
            ]);
        }

        $requestData = $this->setLabels(request()->except(['code', 'type']));

        try {
            $this->associationTypeFieldRepository->update($requestData, $field->id);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.fields.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified field from the association type.
     */
    public function deleteField(string $code, string $fieldCode): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $field = $this->associationTypeFieldRepository->findOneWhere([
            'code'                => $fieldCode,
            'association_type_id' => $associationType->id,
        ]);

        if (! $field) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.fields.not-found', ['code' => $fieldCode]));
        }

        try {
            $this->associationTypeFieldRepository->delete($field->id);

            return $this->successResponse(trans('admin::app.catalog.association_types.fields.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
