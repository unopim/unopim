<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryMediaValidator;
use Webkul\Core\Filesystem\FileStorer;
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
    ) {}

    /**
     * Handles the storage of media files for products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProductMedia()
    {
        request()->validate([
            'file'      => 'required',
            'sku'       => 'required',
            'attribute' => 'required',
        ]);

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCategoryMedia()
    {
        request()->validate([
            'file'           => 'required',
            'code'           => 'required',
            'category_field' => 'required',
        ]);

        $requestData = request()->all();

        $category = $this->categoryRepository->findOneByField('code', $requestData['code']);
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.categories.not-found', ['code' => $requestData['code']]));
        }

        $categoryId = $category->id;

        $validator = $this->categoryMediaValidator->validate($requestData, $categoryId);

        if ($validator instanceof \Illuminate\Validation\Validator && $validator->fails()) {
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
    }

    public function storeSwatchMedia()
    {
        request()->validate([
            'code' => [
                'required',
                'string',
                Rule::exists('attribute_options', 'code'),
            ],
            'file' => 'required|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $requestData = request()->all();

        $attributeOption = $this->attributeOptionRepository->findOneByField('code', $requestData['code']);

        if (! $attributeOption) {
            return $this->modelNotFoundResponse(
                trans('admin::app.catalog.attributes.option.not-found', ['code' => $requestData['code']])
            );
        }

        try {
            $file = request()->file('file');

            if ($file instanceof UploadedFile) {
                $filePath = $this->fileStorer->store(
                    path: 'attribute_option'.DIRECTORY_SEPARATOR.$attributeOption->id,
                    file: $file
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
