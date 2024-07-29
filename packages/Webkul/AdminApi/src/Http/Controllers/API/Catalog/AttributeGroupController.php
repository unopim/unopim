<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeGroupDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
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
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $validator = $this->codeRequireWithUniqueValidator('attribute_groups');

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $requestData = request()->only(['code', 'labels']);
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
     *
     * @return \Illuminate\Http\Response
     */
    public function update(string $code)
    {
        $requestData = request()->only([
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
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
