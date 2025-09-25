<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AttributeDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Attribute\Repositories\AttributeColumnOptionRepository;
use Webkul\Attribute\Repositories\AttributeColumnRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Rules\NotSupportedAttributes;
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
        protected AttributeColumnRepository $attributeColumnRepository,
        protected AttributeColumnOptionRepository $attributeColumnOptionRepository,
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

        $requestData = request()->except(['type', 'code', 'value_per_locale', 'value_per_channel', 'is_unique']);
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
     * Display a single result of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getColumns(string $code)
    {
        try {
            return response()->json(app(AttributeDataSource::class)->getColumnsByAttributeCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single result of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getColumnOptions(string $attributeCode, string $columnCode)
    {
        try {
            return app(AttributeDataSource::class)->getColumnOptionByColumnCode($attributeCode, $columnCode);
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
                trans('admin::app.catalog.attributes.edit.option.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly attribute column in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeColumn(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestDataColumn = request()->all();

        try {
            $errors = [];
            foreach ($requestDataColumn as $columnInputs) {
                $columnInputs = $this->setLabels($columnInputs, 'label');
                $validator = $this->columnValidate($columnInputs, $attribute->id);
                if ($validator->fails()) {
                    $errors[] = $validator->errors();

                    continue;
                }

                $this->attributeColumnRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                ], $columnInputs));
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attributes.edit.column.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly attribute column option in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeColumnOptions(string $attributeCode, string $columnCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $column = $this->attributeColumnRepository->findOneWhere([
            'attribute_id' => $attribute->id,
            'code'         => $columnCode,
        ]);
        if (! $column) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.column.not-found', ['code' => $columnCode]));
        }

        if (! in_array($column->type, ['select', 'multiselect'], true)) {
            return $this->validateErrorResponse([
                'type' => [trans('admin::app.catalog.attributes.invalid-column-type')],
            ]);
        }

        $requestDataColumnOption = request()->all();
        if (! is_array($requestDataColumnOption) || empty($requestDataColumnOption)) {
            return $this->validateErrorResponse([
                'options' => [trans('admin::app.catalog.attributes.column.no-options-provided')],
            ]);
        }

        try {
            $errors = [];
            foreach ($requestDataColumnOption as $columnOptionInputs) {
                $columnOptionInputs = $this->setLabels($columnOptionInputs, 'label');
                $validator = $this->columnOptionValidate($columnOptionInputs, $column->id);
                if ($validator->fails()) {
                    $errors[] = $validator->errors();

                    continue;
                }

                $this->attributeColumnOptionRepository->create(array_merge([
                    'attribute_column_id' => $column->id,
                ], $columnOptionInputs));
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attributes.column.option.create-success'),
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
                trans('admin::app.catalog.attributes.edit.option.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Updates an attribute column in the storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColumn(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $requestData = request()->all();

        try {
            $errors = [];
            foreach ($requestData as $columnInputs) {
                $columnInputs = $this->setLabels($columnInputs, 'label');
                $attributeColumn = $this->attributeColumnRepository->findOneByField('code', $columnInputs['code']);
                if (! $attributeColumn) {
                    $validator = $this->columnValidate($columnInputs, $attribute->id);
                    if ($validator->fails()) {
                        $errors[] = $validator->errors();

                        continue;
                    }

                    $this->attributeColumnRepository->create(array_merge([
                        'attribute_id' => $attribute->id,
                    ], $columnInputs));
                } else {
                    $this->attributeColumnRepository->update($columnInputs, $attributeColumn->id);
                }
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attributes.edit.column.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Updates an attribute column options in the storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColumnOptions(string $attributeCode, string $columnCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
        }

        $column = $this->attributeColumnRepository->findOneWhere([
            'attribute_id' => $attribute->id,
            'code'         => $columnCode,
        ]);
        if (! $column) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.column.not-found', ['code' => $columnCode]));
        }

        if (! in_array($column->type, ['select', 'multiselect'], true)) {
            return $this->validateErrorResponse([
                'type' => [trans('admin::app.catalog.attributes.invalid-column-type')],
            ]);
        }

        $requestDataColumnOption = request()->all();
        if (! is_array($requestDataColumnOption) || empty($requestDataColumnOption)) {
            return $this->validateErrorResponse([
                'options' => [trans('admin::app.catalog.attributes.column.no-options-provided')],
            ]);
        }

        try {
            $errors = [];
            foreach ($requestDataColumnOption as $columnOptionInputs) {
                $columnOptionInputs = $this->setLabels($columnOptionInputs, 'label');
                $attributeColumnOption = $this->attributeColumnOptionRepository->findOneByField('code', $columnOptionInputs['code']);
                if (! $attributeColumnOption) {
                    $validator = $this->columnOptionValidate($columnOptionInputs, $column->id);
                    if ($validator->fails()) {
                        $errors[] = $validator->errors();

                        continue;
                    }

                    $this->attributeColumnOptionRepository->create(array_merge([
                        'attribute_column_id' => $column->id,
                    ], $columnOptionInputs));
                } else {
                    $this->attributeColumnOptionRepository->update($columnOptionInputs, $attributeColumnOption->id);
                }
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.attributes.column.option.update-success'),
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
        ];

        return Validator::make($requestData, $rules);
    }

    /**
     * Validates attribute column data.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function columnValidate(array $requestData, int $attributeId)
    {
        $rules = [
            'code' => ['required',
                Rule::unique('attribute_columns')->where(function ($query) use ($requestData, $attributeId) {
                    return $query->where('code', $requestData['code'])->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'type' => 'required',
        ];

        return Validator::make($requestData, $rules);
    }

    /**
     * Validates attribute column option data.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function columnOptionValidate(array $requestData, int $columnId)
    {
        $rules = [
            'code' => ['required',
                Rule::unique('attribute_column_options')->where(function ($query) use ($requestData, $columnId) {
                    return $query->where('code', $requestData['code'])->where('attribute_column_id', $columnId);
                }),
                new Code,
            ],
        ];

        return Validator::make($requestData, $rules);
    }
}
