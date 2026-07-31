<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeGroupDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAttributeGroupRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAttributeGroupRequest;
use Webkul\Attribute\Repositories\AttributeGroupRepository;

class AttributeGroupController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected AttributeGroupRepository $attributeGroupRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(AttributeGroupDataSource::class)->toJson();
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
            return response()->json(app(AttributeGroupDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeGroupRequest $request): JsonResponse
    {
        $requestData = $request->only(['code', 'labels']);
        $requestData = $this->setLabels($requestData);

        try {
            Event::dispatch('catalog.attribute.group.create.before');
            $attributeGroup = $this->attributeGroupRepository->create($requestData);
            Event::dispatch('catalog.attribute.group.create.after', $attributeGroup);

            return $this->successResponse(
                trans('admin::app.catalog.attribute-groups.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttributeGroupRequest $request, string $code): JsonResponse
    {
        $requestData = $request->only([
            'labels',
        ]);

        $attributeGroup = $this->attributeGroupRepository->findOneByField('code', $code);
        if (! $attributeGroup) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attribute-groups.not-found', ['code' => $code]));
        }

        $requestData = $this->setLabels($requestData);
        $id = $attributeGroup->id;

        try {
            Event::dispatch('catalog.attribute.group.update.before', $id);
            $attributeGroup = $this->attributeGroupRepository->update($requestData, $id);
            Event::dispatch('catalog.attributegroup.update.after', $attributeGroup);

            return $this->successResponse(
                trans('admin::app.catalog.attribute-groups.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Partially update the specified resource.
     */
    public function partialUpdate(UpdateAttributeGroupRequest $request, string $code): JsonResponse
    {
        return $this->update($request, $code);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $code): JsonResponse
    {
        $attributeGroup = $this->attributeGroupRepository->findOneByField('code', $code);
        if (! $attributeGroup) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attribute-groups.not-found', ['code' => $code]));
        }

        if ($attributeGroup->groupMappings()->count()) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.catalog.attribute-groups.attribute-group-error')]],
                trans('admin::app.catalog.attribute-groups.attribute-group-error')
            );
        }

        try {
            Event::dispatch('catalog.attribute.group.delete.before', $attributeGroup->id);
            $this->attributeGroupRepository->delete($attributeGroup->id);
            Event::dispatch('catalog.attribute.group.delete.after', $attributeGroup->id);

            return $this->successResponse(trans('admin::app.catalog.attribute-groups.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
