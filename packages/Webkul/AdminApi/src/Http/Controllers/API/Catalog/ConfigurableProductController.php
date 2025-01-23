<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\ConfigurableProductDataSource;
use Webkul\Product\Type\AbstractType;

class ConfigurableProductController extends ProductController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(ConfigurableProductDataSource::class)->toJson();
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
            return response()->json(app(ConfigurableProductDataSource::class)->getByCode($code));
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
        $validator = Validator::make(request()->all(), [
            'status'            => ['nullable', 'boolean'],
            'parent'            => ['nullable', 'string'],
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'family'            => ['required', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
            'super_attributes'  => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $data = request()->only([
            'status',
            'parent',
            'family',
            'additional',
            'values',
            'super_attributes',
        ]);

        try {
            $family = $this->findFamilyOr404($data['family']);
            $data['type'] = config('product_types.configurable.key');

            $this->validateSuperAttributes($data, $family);

            unset($data['family']);
            $data['attribute_family_id'] = $family->id;
            $data['sku'] = $this->getSkuFromValues($data);

            try {
                $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY]);
            } catch (ValidationException $e) {
                return $this->validateErrorResponse($e->validator->errors()->messages());
            }

            Event::dispatch('catalog.product.create.before');
            $product = $this->productRepository->create($data);
            Event::dispatch('catalog.product.create.after', $product);

            $product = $this->updateProduct($data, $product);

            return $this->successResponse(
                trans('admin::app.catalog.products.create-success'),
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
    public function update(string $sku)
    {
        $validator = Validator::make(request()->all(), [
            'status'            => ['nullable', 'boolean'],
            'parent'            => ['nullable', 'string'],
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'family'            => ['required', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $data = request()->only([
            'status',
            'parent',
            'additional',
            'values',
            'variants',
        ]);

        try {
            $product = $this->findProductOr404($sku);
            $data['sku'] = $this->getSkuFromValues($data);
            $id = $product->id;

            try {
                $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY], productId: $id);
            } catch (ValidationException $e) {
                return $this->validateErrorResponse($e->validator->errors()->messages());
            }

            $data['super_attributes'] = $product->super_attributes->pluck('code')?->toArray();
            $data['variants'] = $this->setVaraints($product, $data, $data['sku']);

            Event::dispatch('catalog.product.update.before', $id);

            $product = $this->updateProduct($data, $product);

            Event::dispatch('catalog.product.update.after', $product);

            return $this->successResponse(
                trans('admin::app.catalog.products.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Patch the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function partialUpdate(string $sku)
    {
        $validator = Validator::make(request()->all(), [
            'parent'            => ['nullable', 'string'],
            'family'            => ['nullable', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['nullable', 'array'],
            'values.common.sku' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $data = request()->only([
            'parent',
            'family',
            'additional',
            'values',
            'super_attributes',
            'variants',
        ]);
        try {
            $product = $this->findProductOr404($sku);
            $product = $this->patchProduct($product, $data);

            return $this->successResponse(
                trans('admin::app.catalog.products.update-success'),
                Response::HTTP_OK,

            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}
