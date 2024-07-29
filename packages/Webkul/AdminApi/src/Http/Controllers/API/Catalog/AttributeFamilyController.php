<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeFamilyDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Attribute\Repositories\AttributeFamilyGroupMappingRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeFamilyController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeFamilyGroupMappingRepository $attributeFamilyGroupMappingRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(AttributeFamilyDataSource::class)->toJson();
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
            return response()->json(app(AttributeFamilyDataSource::class)->getByCode($code));
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
        $validator = $this->codeRequireWithUniqueValidator('attribute_families');

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $requestData = request()->all();
        $requestData = $this->setLabels($requestData);
        $errors = [];
        $requestData = $this->normalize($requestData, $errors);

        if ($errors) {
            return $this->validateErrorResponse($errors);
        }

        try {
            Event::dispatch('catalog.attribute_family.create.before');
            $attributeFamily = $this->attributeFamilyRepository->create($requestData);
            Event::dispatch('catalog.attribute_family.create.after', $attributeFamily);

            return $this->successResponse(
                trans('admin::app.catalog.families.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(string $code)
    {
        $attributeFamily = $this->attributeFamilyRepository->findOneByField('code', $code);
        if (! $attributeFamily) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.families.not-found', ['code' => $code]));
        }

        $requestData = request()->except(['code']);
        $requestData = $this->setLabels($requestData);
        $id = $attributeFamily->id;
        $errors = [];
        $requestData = $this->normalize($requestData, $errors, $id);

        if ($errors) {
            return $this->validateErrorResponse($errors);
        }

        try {
            Event::dispatch('catalog.attribute_family.update.before', $id);
            $attributeFamily = $this->attributeFamilyRepository->update($requestData, $id);
            Event::dispatch('catalog.attribute_family.update.after', $attributeFamily);

            return $this->successResponse(
                trans('admin::app.catalog.families.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Normalize custom attributes, and custom attribute groups data.
     *
     * @return array
     */
    private function normalize(array $requestData, &$errors, ?int $familyId = null)
    {
        $attributeGroup = [];
        foreach ($requestData['attribute_groups'] as $key => $value) {
            $groupId = $this->attributeGroupRepository->findOneByField('code', $value['code'])?->id;
            if (! $groupId) {
                $errors[] = [
                    'code' => trans('admin::app.catalog.attribute-groups.not-found', ['code' => $value['code']]),
                ];
            }

            if ($familyId) {
                $groupMappingId = $this->attributeFamilyGroupMappingRepository->findWhere([
                    'attribute_group_id'  => $groupId,
                    'attribute_family_id' => $familyId,
                ])->first()?->id;

                $value['attribute_groups_mapping'] = $groupMappingId;
            }

            $attributeGroup[$groupId] = $this->setAttributeAndPosition($value, $errors);
        }

        $requestData['attribute_groups'] = $attributeGroup;

        return $requestData;
    }

    /**
     * Set attribute and position for custom attributes.
     *
     * This method iterates over the custom attributes provided in the data array,
     * sets the position for each attribute, and retrieves the ID of the attribute
     * using its code. The retrieved ID is then assigned to the attribute.
     *
     * @return array The modified data array with attribute IDs and positions set.
     */
    private function setAttributeAndPosition(array $data, &$errors)
    {
        foreach ($data['custom_attributes'] as $key => $value) {
            $data['custom_attributes'][$key]['position'] = $value['position'];
            $attributeId = $this->attributeRepository->findOneByField('code', $value['code'])?->id;

            if (! $attributeId) {
                $errors[] = [
                    'code' => trans('admin::app.catalog.attributes.not-found', ['code' => $value['code']]),
                ];

                continue;
            }

            $data['custom_attributes'][$key]['id'] = $attributeId;
        }

        return $data;
    }
}
