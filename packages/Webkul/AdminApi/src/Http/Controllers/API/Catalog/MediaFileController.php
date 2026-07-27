<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreCategoryMediaRequest;
use Webkul\AdminApi\Http\Requests\Catalog\StoreProductMediaRequest;
use Webkul\AdminApi\Http\Requests\Catalog\StoreSwatchMediaRequest;
use Webkul\Attribute\Contracts\AttributeOption;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryMediaValidator;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\API\UploadMediaValidator;

class MediaFileController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository,
        protected CategoryMediaValidator $categoryMediaValidator,
        protected UploadMediaValidator $mediaValidator,
        protected FileStorer $fileStorer,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    /**
     * Handles the storage of media files for products.
     */
    public function storeProductMedia(StoreProductMediaRequest $request): JsonResponse
    {
        $requestData = request()->all();
        try {
            $product = $this->findProductOr404($requestData['sku']);
            $productId = $product->id;
            $this->mediaValidator->validate(
                $requestData,
                $productId
            );
        } catch (ValidationException|ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {
                return $this->storeExceptionLog($e);
            }

            return $this->validateErrorResponse($e->validator->errors()->messages());
        }

        $attributeValue = $requestData['file'];
        $attribute = $requestData['attribute'];

        try {
            $attributeValue = is_array($attributeValue) ? $attributeValue : [$attributeValue];
            $filePath = [];

            foreach ($attributeValue as $value) {
                if ($value instanceof UploadedFile) {
                    $filePath[] = $this->fileStorer->store(
                        path: 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.$attribute,
                        file: $value
                    );
                }
            }

            $filePath = implode(',', $filePath);

            $this->assignMediaToProductAttribute($product, $attribute, $filePath);

            return $this->successResponse(
                trans('admin::app.catalog.products.upload-success'),
                Response::HTTP_OK,
                [
                    'attribute' => $attribute,
                    'sku'       => $requestData['sku'],
                    'filePath'  => $filePath,
                ]
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Handles the storage of media files for categories.
     */
    public function storeCategoryMedia(StoreCategoryMediaRequest $request): JsonResponse
    {
        $requestData = request()->all();

        $category = $this->categoryRepository->findOneByField('code', $requestData['code']);
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.not-found', ['code' => $requestData['code']]));
        }

        $categoryId = $category->id;

        $validator = $this->categoryMediaValidator->validate($requestData, $categoryId);

        if ($validator instanceof Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        $fieldValue = $requestData['file'];
        $field = $requestData['category_field'];

        try {
            if ($fieldValue instanceof UploadedFile) {
                $filePath = $this->fileStorer->store(
                    path: 'category'.DIRECTORY_SEPARATOR.$categoryId.DIRECTORY_SEPARATOR.$field,
                    file: $fieldValue
                );

                return $this->successResponse(
                    trans('admin::app.catalog.categories.upload-success'),
                    Response::HTTP_OK,
                    [
                        'field'    => $field,
                        'code'     => $requestData['code'],
                        'filePath' => $filePath,
                    ]
                );
            }
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }

        return $this->validateErrorResponse([
            'file' => trans('admin::app.catalog.categories.upload-failure'),
        ]);
    }

    /**
     * Handles the storage of media files for swatch attribute.
     */
    public function storeSwatchMedia(StoreSwatchMediaRequest $request): JsonResponse
    {
        $requestData = request()->all();

        $attribute = $this->attributeRepository->findOneByField('code', $requestData['attribute_code']);

        if (! $attribute) {
            return $this->modelNotFoundResponse(
                trans('admin::app.catalog.attributes.not-found', ['code' => $requestData['attribute_code']])
            );
        }

        $attributeOption = $this->attributeOptionRepository->findOneWhere([
            'code'         => $requestData['code'],
            'attribute_id' => $attribute->id,
        ]);

        if (! $attributeOption) {
            return $this->modelNotFoundResponse(
                trans('admin::app.catalog.products.edit.types.configurable.variant-attribute-option-not-found', ['attributes' => $requestData['code']])
            );
        }

        if ($attribute->swatch_type !== 'image') {
            return $this->validateErrorResponse([
                'file' => trans('admin::app.catalog.attributes.create.invalid-swatch-type', ['attribute' => $requestData['attribute_code'], 'type' => 'Upload', 'swatch_type' => 'Image']),
            ]);
        }

        try {
            $file = request()->file('file');

            if ($file instanceof UploadedFile) {
                $extension = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());

                $filePath = $this->fileStorer->storeAs(
                    path: 'attribute_option'.DIRECTORY_SEPARATOR.$attributeOption->id,
                    name: Str::random(40).'.'.$extension,
                    file: $file,
                );

                $updatedOption = $this->attributeOptionRepository->update([
                    'swatch_value' => $filePath,
                ], $attributeOption->id);

                return $this->successResponse(
                    trans('admin::app.catalog.attribute-options.update-success'),
                    Response::HTTP_OK,
                    [
                        'code'             => $requestData['code'],
                        'swatch_value'     => $updatedOption->swatch_value,
                        'swatch_value_url' => $updatedOption->swatch_value_url,
                    ]
                );
            }

            return $this->validateErrorResponse(['file' => ['Invalid file uploaded.']]);
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Return the stored media path(s) for a product attribute at the resolved scope.
     */
    public function getProductMedia(): JsonResponse
    {
        $product = $this->productRepository->findByField('sku', request('sku'))->first();
        if (! $product) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.products.product-not-found', ['sku' => request('sku')]));
        }

        [, $values, $path] = $this->resolveProductMediaSlot($product, (string) request('attribute'));

        return response()->json([
            'data' => $this->splitPaths(Arr::get($values, $path)),
        ]);
    }

    /**
     * Delete a product attribute's media file(s) from storage and clear the value.
     */
    public function deleteProductMedia(): JsonResponse
    {
        $product = $this->productRepository->findByField('sku', request('sku'))->first();
        if (! $product) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.products.product-not-found', ['sku' => request('sku')]));
        }

        [, $values, $path] = $this->resolveProductMediaSlot($product, (string) request('attribute'));

        $stored = $this->splitPaths(Arr::get($values, $path));
        if ($stored === []) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.products.upload-failure'));
        }

        try {
            $this->deleteFiles($stored);
            Arr::forget($values, $path);
            $product->values = $values;
            $product->save();

            return $this->successResponse(trans('admin::app.catalog.products.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Return the stored media path for a category field.
     */
    public function getCategoryMedia(): JsonResponse
    {
        $category = $this->categoryRepository->findOneByField('code', request('code'));
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.not-found', ['code' => request('code')]));
        }

        $field = (string) request('category_field');
        $values = $category->additional_data ?? [];

        return response()->json([
            'data' => $this->splitPaths(Arr::get($values, "common.$field") ?? Arr::get($values, 'locale_specific.'.core()->getDefaultLocaleCodeFromDefaultChannel().".$field")),
        ]);
    }

    /**
     * Delete a category field's media file(s) from storage and clear the value.
     */
    public function deleteCategoryMedia(): JsonResponse
    {
        $category = $this->categoryRepository->findOneByField('code', request('code'));
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.not-found', ['code' => request('code')]));
        }

        $field = (string) request('category_field');
        $values = $category->additional_data ?? [];
        $locale = core()->getDefaultLocaleCodeFromDefaultChannel();
        $key = Arr::has($values, "common.$field") ? "common.$field" : "locale_specific.$locale.$field";

        $stored = $this->splitPaths(Arr::get($values, $key));
        if ($stored === []) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.upload-failure'));
        }

        try {
            $this->deleteFiles($stored);
            Arr::forget($values, $key);
            $category->additional_data = $values;
            $category->save();

            return $this->successResponse(trans('admin::app.catalog.categories.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Return the swatch image path for an attribute option.
     */
    public function getSwatchMedia(): JsonResponse
    {
        $option = $this->findSwatchOption();
        if (! $option instanceof Model) {
            return $option;
        }

        return response()->json(['data' => $this->splitPaths($option->swatch_value)]);
    }

    /**
     * Delete an attribute option's swatch image from storage and clear the value.
     */
    public function deleteSwatchMedia(): JsonResponse
    {
        $option = $this->findSwatchOption();
        if (! $option instanceof Model) {
            return $option;
        }

        $stored = $this->splitPaths($option->swatch_value);
        if ($stored === []) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attribute-options.swatch-not-found'));
        }

        try {
            $this->deleteFiles($stored);
            $this->attributeOptionRepository->update(['swatch_value' => null], $option->id);

            return $this->successResponse(trans('admin::app.catalog.attribute-options.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Resolve the requested attribute option for a swatch, or a 404 response.
     *
     * @return AttributeOption|JsonResponse
     */
    private function findSwatchOption()
    {
        $attribute = $this->attributeRepository->findOneByField('code', request('attribute_code'));
        if (! $attribute) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.attributes.not-found', ['code' => request('attribute_code')]));
        }

        $option = $this->attributeOptionRepository->findOneWhere([
            'code'         => request('code'),
            'attribute_id' => $attribute->id,
        ]);
        if (! $option) {
            return $this->modelNotFoundResponse(
                trans('admin::app.catalog.products.edit.types.configurable.variant-attribute-option-not-found', ['attributes' => request('code')])
            );
        }

        return $option;
    }

    /**
     * Dot-path into the product values JSON for an attribute at the request's scope.
     *
     * @return array{0: mixed, 1: array<string, mixed>, 2: string}
     */
    private function resolveProductMediaSlot($product, string $attributeCode): array
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        $values = $product->values ?? [];
        $channel = request()->input('channel') ?: core()->getDefaultChannelCode();
        $locale = request()->input('locale') ?: core()->getDefaultLocaleCodeFromDefaultChannel();

        if ($attribute?->value_per_channel && $attribute?->value_per_locale) {
            $path = "channel_locale_specific.$channel.$locale.$attributeCode";
        } elseif ($attribute?->value_per_channel) {
            $path = "channel_specific.$channel.$attributeCode";
        } elseif ($attribute?->value_per_locale) {
            $path = "locale_specific.$locale.$attributeCode";
        } else {
            $path = "common.$attributeCode";
        }

        return [$attribute, $values, $path];
    }

    /**
     * @return array<int, string>
     */
    private function splitPaths(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }

    /**
     * Persist the uploaded file path into the product's values JSON under the
     * correct scope (common / locale / channel / channel-locale), using the
     * attribute's scope flags and the request's channel/locale (falling back
     * to defaults).
     */
    protected function assignMediaToProductAttribute($product, string $attributeCode, string $filePath): void
    {
        if ($filePath === '') {
            return;
        }

        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);

        if (! $attribute) {
            return;
        }

        $values = $product->values ?? [];
        $channel = request()->input('channel') ?: core()->getDefaultChannelCode();
        $locale = request()->input('locale') ?: core()->getDefaultLocaleCodeFromDefaultChannel();

        if ($attribute->value_per_channel && $attribute->value_per_locale) {
            $values['channel_locale_specific'][$channel][$locale][$attributeCode] = $filePath;
        } elseif ($attribute->value_per_channel) {
            $values['channel_specific'][$channel][$attributeCode] = $filePath;
        } elseif ($attribute->value_per_locale) {
            $values['locale_specific'][$locale][$attributeCode] = $filePath;
        } else {
            $values['common'][$attributeCode] = $filePath;
        }

        $product->values = $values;
        $product->save();
    }

    /**
     * Finds a product by its SKU and throws a ModelNotFoundException if not found.
     *
     * @param  string  $sku  The SKU of the product to be found.
     * @return Product The found product.
     *
     * @throws ModelNotFoundException If the product is not found.
     */
    protected function findProductOr404(string $sku)
    {
        $product = $this->productRepository->findByField('sku', $sku)->first();
        if (! $product) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.products.product-not-found', ['sku' => $sku])
            );
        }

        return $product;
    }
}
