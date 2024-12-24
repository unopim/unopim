<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\SimpleProductDataSource;
use Webkul\Product\Type\AbstractType;

class SimpleProductController extends ProductController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(SimpleProductDataSource::class)->toJson();
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
            return response()->json(app(SimpleProductDataSource::class)->getByCode($code));
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
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'parent'            => ['nullable', 'string'],
            'family'            => ['required', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
            'variant'           => ['nullable', 'array'],
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
            'variant',
        ]);

        try {
            $family = $this->findFamilyOr404($data['family']);
            $product = null;
            $data['attribute_family_id'] = $family->id;
            unset($data['family']);

            $data['type'] = config('product_types.simple.key');
            $data['sku'] = $this->getSkuFromValues($data);

            try {
                $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY]);
            } catch (ValidationException $e) {
                return $this->validateErrorResponse($e->validator->errors()->messages());
            }

            if ($data['parent']) {
                $data[AbstractType::PRODUCT_VALUES_KEY][AbstractType::COMMON_VALUES_KEY] = array_merge(
                    $data[AbstractType::PRODUCT_VALUES_KEY][AbstractType::COMMON_VALUES_KEY],
                    ($data['variant']['attributes'] ?? [])
                );

                $product = $this->createOrUpdateVariant($data);
                unset($data['variant']);
            }

            if (! $product) {
                Event::dispatch('catalog.product.create.before');
                $product = $this->productRepository->create($data);
                Event::dispatch('catalog.product.create.after', $product);
            }

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
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'parent'            => ['nullable', 'string'],
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
        ]);

        try {
            $product = $this->findProductOr404($sku);

            $id = $product->id;

            $data['sku'] = $this->getSkuFromValues($data);

            try {
                $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY], productId: $id);
            } catch (ValidationException $e) {
                return $this->validateErrorResponse($e->validator->errors()->messages());
            }

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
}
