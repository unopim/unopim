<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\CategoryDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryValidator;

class CategoryController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryValidator $categoryValidator
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(CategoryDataSource::class)->toJson();
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
            return response()->json(app(CategoryDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Delete the single resource.
     */
    public function delete(string $code): JsonResponse
    {
        try {
            $deleted = app(CategoryDataSource::class)->deleteByCode($code);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => trans('admin::app.catalog.categories.delete-success'),
                    'code'    => $code,
                ], 200);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => $code,
            ], 404);
        } catch (\Exception $e) {

            if ($e->getMessage() === trans('admin::app.catalog.categories.delete-category-root')) {
                return response()->json([
                    'success' => false,
                    'message' => trans('admin::app.catalog.categories.delete-category-root'),
                    'code'    => $code,
                ], 403);
            }

            return $this->storeExceptionLog($e);
        }

        return response()->json([
            'success' => false,
            'message' => trans('admin::app.catalog.categories.delete-failed'),
            'code'    => $code,
        ], 404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $requestData = request()->only([
            'code',
            'parent',
            'additional_data',
        ]);

        $parentId = $this->getParentIdByCode($requestData['parent']);
        unset($requestData['parent']);
        $requestData['parent_id'] = $parentId;

        $validator = $this->categoryValidator->validate($requestData);

        if ($validator instanceof Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        try {
            $this->sanitizeInput($requestData);
            Event::dispatch('catalog.category.create.before');
            $category = $this->categoryRepository->create($requestData);
            Event::dispatch('catalog.category.create.after', $category);

            return $this->successResponse(
                trans('admin::app.catalog.categories.create-success'),
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
        $category = $this->categoryRepository->findOneByField('code', $code);
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.category.not-found', ['code' => $code]));
        }

        $requestData = request()->only(['parent', 'additional_data']);
        $parentId = null;
        if (isset($requestData['parent'])) {
            $parentId = $this->getParentIdByCode($requestData['parent']);
        }

        unset($requestData['parent']);
        $requestData['parent_id'] = $parentId;
        $id = $category->id;

        $validator = $this->categoryValidator->validate($requestData, $id);

        if ($validator instanceof Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        try {
            $this->sanitizeInput($requestData);
            Event::dispatch('catalog.category.update.before', $id);
            $category = $this->categoryRepository->update($requestData, $id);
            Event::dispatch('catalog.category.update.after', $category);

            return $this->successResponse(
                trans('admin::app.catalog.categories.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    public function sanitizeInput(&$requestData)
    {
        $fields = $this->categoryFieldRepository->findByField('status', true)
            ->where('enable_wysiwyg', '==', 1)
            ->where('type', '==', 'textarea');

        foreach ($fields as $field) {
            if ($field->value_per_locale) {
                foreach ($requestData['additional_data']['locale_specific'] ?? [] as $locale => $values) {
                    foreach ($values ?? [] as $code => $value) {
                        if (empty($value) || $field->code !== $code) {
                            continue;
                        }
                        $requestData['additional_data']['locale_specific'][$locale][$code] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }
            } else {
                foreach ($requestData['additional_data']['common'] ?? [] as $code => $value) {
                    if (empty($value) || $field->code !== $code) {
                        continue;
                    }
                    $requestData['additional_data']['common'][$code] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }

    /**
     * Patch the resource.
     */
    public function partialUpdate(string $code)
    {

        $category = $this->categoryRepository->findOneByField('code', $code);
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.not-found', ['code' => $code]));
        }

        $requestData = request()->only(['parent', 'additional_data']);
        $parentId = null;

        if (isset($requestData['parent'])) {
            $parentId = $this->getParentIdByCode($requestData['parent']);
        }

        unset($requestData['parent']);
        $requestData['parent_id'] = $parentId;

        $validator = $this->categoryValidator->validate($requestData, $category->id);
        if ($validator instanceof Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        try {
            $this->sanitizeInput($requestData);
            Event::dispatch('catalog.category.update.before', $category->id);
            if (isset($requestData['parent_id'])) {
                $category->parent_id = $requestData['parent_id'];
            }

            if (isset($requestData['additional_data'])) {
                $existingAdditionalData = $category->additional_data;
                foreach ($requestData['additional_data'] as $key => $value) {
                    if (array_key_exists($key, $existingAdditionalData)) {
                        $existingAdditionalData[$key] = is_array($existingAdditionalData[$key]) && is_array($value)
                            ? array_merge($existingAdditionalData[$key], $value)
                            : $value;

                        continue;
                    }

                    $existingAdditionalData[$key] = $value;

                }
                $category->additional_data = json_encode($existingAdditionalData);
            }
            $this->categoryRepository->update($requestData, $category->id);
            Event::dispatch('catalog.category.update.after', $category);

            return $this->successResponse(
                trans('admin::app.catalog.categories.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Retrieves the ID of a category based on its code.
     *
     * @return int|null The ID of the category if found, otherwise null.
     */
    private function getParentIdByCode(string $code)
    {
        return $this->categoryRepository->findOneByField('code', $code)?->id;
    }
}
