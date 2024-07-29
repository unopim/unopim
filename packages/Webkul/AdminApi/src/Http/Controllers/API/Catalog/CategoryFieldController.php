<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\CategoryFieldDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Category\Repositories\CategoryFieldOptionRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Rules\FieldTypes;
use Webkul\Category\Rules\ValidationTypes;
use Webkul\Core\Rules\Code;

class CategoryFieldController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryFieldOptionRepository $categoryFieldOptionRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(CategoryFieldDataSource::class)->toJson();
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
            return response()->json(app(CategoryFieldDataSource::class)->getByCode($code));
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
                new FieldTypes(),
            ],
        ];

        if (isset($requestData['validation']) && $requestData['validation']) {
            $rules['validation'] = [new ValidationTypes()];
        }

        $validator = $this->codeRequireWithUniqueValidator(
            'category_fields',
            $rules
        );

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $requestData = $this->setLabels($requestData);
        $requestData = $this->setDefaultValues($requestData);

        try {
            Event::dispatch('catalog.category_field.create.before');

            $categoryField = $this->categoryFieldRepository->create($requestData);

            Event::dispatch('catalog.category_field.create.after', $categoryField);

            return $this->successResponse(
                trans('admin::app.catalog.category_fields.create-success'),
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
        $categoryField = $this->categoryFieldRepository->findOneByField('code', $code);
        if (! $categoryField) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.category_fields.not-found', ['code' => $code]));
        }

        $requestData = request()->except(['code', 'type', 'value_per_locale', 'is_unique']);
        $requestData = $this->setLabels($requestData);
        $requestData['enable_wysiwyg'] = $categoryField->type == 'textarea' ? $requestData['enable_wysiwyg'] : 0;
        $id = $categoryField->id;

        try {
            Event::dispatch('catalog.category_field.update.before');
            $categoryField = $this->categoryFieldRepository->update($requestData, $id);
            Event::dispatch('catalog.category_field.update.after', $categoryField);

            return $this->successResponse(
                trans('admin::app.catalog.category_fields.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Sets default values for the given request data array.
     *
     * @return array The updated request data array with default values.
     */
    private function setDefaultValues(array $requestData)
    {
        $requestData['status'] = $requestData['status'] ?? 1;
        $requestData['position'] = $requestData['position'] ?? 0;
        $requestData['is_required'] = $requestData['is_required'] ?? 0;
        $requestData['is_unique'] = $requestData['is_unique'] ?? 0;
        $requestData['value_per_locale'] = $requestData['value_per_locale'] ?? 0;
        $requestData['enable_wysiwyg'] = $requestData['enable_wysiwyg'] ?? 0;
        $requestData['section'] = $requestData['section'] ?? 'left';
        $requestData['validation'] = $requestData['validation'] ?? null;
        $requestData['regex_pattern'] = $requestData['regex_pattern'] ?? null;

        return $requestData;
    }

    /**
     * Display a single result of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOptions($code)
    {
        try {
            return response()->json(app(CategoryFieldDataSource::class)->getOptionsByFieldCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOption(string $fieldCode)
    {
        $categoryField = $this->categoryFieldRepository->findOneByField('code', $fieldCode);
        if (! $categoryField) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.category_fields.not-found', ['code' => $fieldCode]));
        }

        $requestData = request()->all();

        try {
            $errors = [];
            foreach ($requestData as $optionInputs) {
                $optionInputs = $this->setLabels($optionInputs, 'label');

                $validator = $this->optionValidate($optionInputs, $categoryField->id);

                if ($validator->fails()) {
                    $errors[] = $validator->errors();

                    continue;
                }

                $this->categoryFieldOptionRepository->create(array_merge([
                    'category_field_id' => $categoryField->id,
                ], $optionInputs));
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.category-fields-options.create-success'),
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
    public function updateOption(string $fieldCode)
    {
        $categoryField = $this->categoryFieldRepository->findOneByField('code', $fieldCode);
        if (! $categoryField) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.category_fields.not-found', ['code' => $fieldCode]));
        }

        $requestData = request()->all();

        try {
            $errors = [];
            foreach ($requestData as $optionInputs) {
                $optionInputs = $this->setLabels($optionInputs, 'label');

                $categoryFieldOption = $this->categoryFieldOptionRepository->findOneByField('code', $optionInputs['code']);

                if (! $categoryFieldOption) {
                    $validator = $this->optionValidate($optionInputs, $categoryField->id);

                    if ($validator->fails()) {
                        $errors[] = $validator->errors();

                        continue;
                    }

                    $this->categoryFieldOptionRepository->create(array_merge([
                        'category_field_id' => $categoryField->id,
                    ], $optionInputs));
                } else {
                    $this->categoryFieldOptionRepository->update($optionInputs, $categoryFieldOption->id);
                }
            }

            if (! empty($errors)) {
                return $this->validateErrorResponse($errors);
            }

            return $this->successResponse(
                trans('admin::app.catalog.category-fields-options.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Validates the category field option data.
     *
     * @return \Illuminate\Contracts\Validation\Validator The Validator instance with the applied rules.
     */
    private function optionValidate(array $requestData, int $categoryFieldId)
    {
        $rules = [
            'code' => ['required',
                Rule::unique('category_field_options')->where(function ($query) use ($requestData, $categoryFieldId) {
                    return $query->where('code', $requestData['code'])->where('category_field_id', $categoryFieldId);
                }),
                new Code(),
            ],
        ];

        return Validator::make($requestData, $rules);
    }
}
