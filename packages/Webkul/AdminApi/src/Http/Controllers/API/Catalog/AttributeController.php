<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Attribute\Rules\ValidationTypes;
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $requestData = request()->all();
        $rules = [
            'type' => [
                'required',
                new AttributeTypes,
            ],
            'code' => [
                'required',
                sprintf('unique:%s,code', 'attributes'),
                new Code,
                new NotSupportedAttributes,
            ],
            'swatch_type' => [
                'nullable',
                new SwatchTypes,
            ],
        ];

        if (isset($requestData['validation']) && $requestData['validation']) {
            $rules['validation'] = [new ValidationTypes];
        }

        $validator = $this->codeRequireWithUniqueValidator(
            'attributes',
            $rules
        );

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(string $code)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $code]));
        }

        $requestData = request()->except(['type', 'code', 'swatch_type', 'value_per_locale', 'value_per_channel', 'is_unique']);
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
     * Display a single result of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOptions(string $code)
    {
        try {
            return response()->json(app(AttributeDataSource::class)->getOptionsByAttributeCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly attribute option in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOption(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestData = request()->all();

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOption(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestData = request()->all();

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
            'swatch_value' => [
                function ($attribute, $value, $fail) use ($attributeId) {
                    if (! empty($value)) {
                        $attr = $this->attributeRepository->find($attributeId);

                        $isValid = $attr?->type === 'select' && in_array($attr?->swatch_type, ['color', 'image']);

                        if (! $isValid) {
                            $fail(trans('admin::app.catalog.attributes.create.invalid-swatch-type', [
                                'attribute'   => $attribute,
                                'type'        => $attr?->type,
                                'swatch_type' => $attr?->swatch_type ?? 'none',
                            ]));
                        }
                    }
                },
            ],
        ];

        return Validator::make($requestData, $rules);
    }
}
