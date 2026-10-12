<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAttributeRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAttributeRequest;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\ValidSwatchValue;
use Webkul\Core\Rules\Code;

class AttributeController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(AttributeDataSource::class)->toJson();
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
            return response()->json(app(AttributeDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $requestData = $request->all();

        if (array_is_list($requestData) && count($requestData) > 0) {
            return $this->validateErrorResponse([
                'payload' => [trans('admin::app.catalog.attributes.create.single-object-only')],
            ]);
        }

        $requestData = $this->setLabels($requestData);

        try {
            Event::dispatch('catalog.attribute.create.before');
            $attribute = $this->attributeRepository->create($requestData);
            Event::dispatch('catalog.attribute.create.after', $attribute);

            return $this->successResponse(
                trans('admin::app.catalog.attributes.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttributeRequest $request, string $code): JsonResponse
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $code]));
        }

        $immutable = array_intersect(['type', 'code', 'swatch_type', 'value_per_locale', 'value_per_channel', 'is_unique'], array_keys($request->all()));
        if (! empty($immutable)) {
            return $this->validateErrorResponse([
                'immutable' => [trans('admin::app.catalog.attributes.immutable-fields', ['fields' => implode(', ', $immutable)])],
            ]);
        }

        $requestData = $request->except(['type', 'code', 'swatch_type', 'value_per_locale', 'value_per_channel', 'is_unique']);

        $requestData = $this->setLabels($requestData);
        $id = $attribute->id;

        try {
            Event::dispatch('catalog.attribute.update.before', $id);
            $attribute = $this->attributeRepository->update($requestData, $id);
            Event::dispatch('catalog.attribute.update.after', $attribute);

            return $this->successResponse(
                trans('admin::app.catalog.attributes.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Partially update the specified resource.
     */
    public function partialUpdate(UpdateAttributeRequest $request, string $code): JsonResponse
    {
        return $this->update($request, $code);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $code): JsonResponse
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $code]));
        }

        if (
            ! $attribute->canBeDeleted()
            || DB::table('product_super_attributes')->where('attribute_id', $attribute->id)->count() > 0
        ) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.catalog.attributes.index.datagrid.delete-failed')]],
                trans('admin::app.catalog.attributes.index.datagrid.delete-failed')
            );
        }

        try {
            Event::dispatch('catalog.attribute.delete.before', $attribute->id);
            $this->attributeRepository->delete($attribute->id);
            Event::dispatch('catalog.attribute.delete.after', $attribute->id);

            return $this->successResponse(trans('admin::app.catalog.attributes.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function getOptions(string $code): JsonResponse
    {
        try {
            return response()->json(app(AttributeDataSource::class)->getOptionsByAttributeCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly attribute option in storage.
     */
    public function storeOption(string $attributeCode): JsonResponse
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestData = $this->normalizeOptionsPayload(request()->all());

        try {
            $errors = [];
            foreach ($requestData as $optionInputs) {
                $optionInputs = $this->setLabels($optionInputs, 'label');
                $validator = $this->optionValidate($optionInputs, $attribute->id);
                if ($validator->fails()) {
                    $errors[] = $validator->errors();

                    continue;
                }

                $this->attributeOptionRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                ], $optionInputs));
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attribute-options.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Updates an attribute option in the storage.
     */
    public function updateOption(string $attributeCode): JsonResponse
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestData = $this->normalizeOptionsPayload(request()->all());

        try {
            $errors = [];
            foreach ($requestData as $optionInputs) {
                $optionInputs = $this->setLabels($optionInputs, 'label');
                $attributeOption = $this->attributeOptionRepository->findOneByField('code', $optionInputs['code']);
                if (! $attributeOption) {
                    $validator = $this->optionValidate($optionInputs, $attribute->id);
                    if ($validator->fails()) {
                        $errors[] = $validator->errors();

                        continue;
                    }

                    $this->attributeOptionRepository->create(array_merge([
                        'attribute_id' => $attribute->id,
                    ], $optionInputs));
                } else {
                    $this->attributeOptionRepository->update($optionInputs, $attributeOption->id);
                }
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attribute-options.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Delete a single option of the attribute, identified by its code.
     */
    public function deleteOption(string $attributeCode, string $optionCode): JsonResponse
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $option = $this->attributeOptionRepository->findOneWhere([
            'code'         => $optionCode,
            'attribute_id' => $attribute->id,
        ]);
        if (! $option) {
            return $this->modelNotFoundResponse(
                trans('admin::app.catalog.products.edit.types.configurable.variant-attribute-option-not-found', ['attributes' => $optionCode])
            );
        }

        try {
            $this->attributeOptionRepository->delete($option->id);

            return $this->successResponse(trans('admin::app.catalog.attribute-options.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Normalizes the options payload so a single option object is treated the
     * same as a list containing one option.
     *
     * @param  array<mixed>  $requestData
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptionsPayload(array $requestData): array
    {
        if (empty($requestData) || array_is_list($requestData)) {
            return $requestData;
        }

        return [$requestData];
    }

    /**
     * Validates attribute option data.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function optionValidate(array $requestData, int $attributeId)
    {
        $rules = [
            'code' => ['required',
                Rule::unique('attribute_options')->where(function ($query) use ($requestData, $attributeId) {
                    return $query->where('code', $requestData['code'])->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'swatch_value' => [new ValidSwatchValue($attributeId)],
        ];

        return Validator::make($requestData, $rules);
    }
}
