<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\ConfigurableProductDataSource;
use Webkul\AdminApi\Http\Requests\Catalog\PartialUpdateConfigurableProductRequest;
use Webkul\AdminApi\Http\Requests\Catalog\StoreConfigurableProductRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateConfigurableProductRequest;
use Webkul\Product\Repositories\VariantStructureRepository;
use Webkul\Product\Services\VariantStructurePlanner;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Type\Configurable;

class ConfigurableProductController extends ProductController
{
    /**
     * Resolves the `variant_structure` code on a create request to a structure owned
     * by the resolved family and reconciles it with the optional `super_attributes`
     * list. Returns the validation-failure response when the code is unknown for that
     * family or the two disagree, and null once `$data` is consistent.
     */
    protected function applyVariantStructureReference(array &$data, string $structureCode, string $familyCode, int $familyId): ?JsonResponse
    {
        $structure = app(VariantStructureRepository::class)->findOneWhere([
            'attribute_family_id' => $familyId,
            'code'                => $structureCode,
        ]);

        if (! $structure) {
            return $this->validateErrorResponse([
                'variant_structure' => [trans('admin::app.catalog.products.variant-structure-not-found', ['code' => $structureCode, 'family' => $familyCode])],
            ]);
        }

        $structure->load('axes.attribute');

        $axisCodes = app(VariantStructurePlanner::class)->allAxisCodes($structure);

        if (empty($data['super_attributes'])) {
            $data['super_attributes'] = $axisCodes;
        } elseif (
            array_diff($axisCodes, $data['super_attributes']) !== []
            || array_diff($data['super_attributes'], $axisCodes) !== []
        ) {
            return $this->validateErrorResponse([
                'super_attributes' => [trans('admin::app.catalog.products.variant-structure-axis-mismatch', [
                    'code' => $structureCode,
                    'axes' => implode(', ', $axisCodes),
                ])],
            ]);
        }

        $data['variant_structure_id'] = $structure->id;

        return null;
    }

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
     * A rich `associations` payload is validated before the product row is created,
     * so an invalid link's `additional_data` aborts with nothing persisted.
     */
    public function store(StoreConfigurableProductRequest $request): JsonResponse
    {
        if ($request->input('type') === 'variant_group') {
            try {
                return $this->storeVariantGroup($request);
            } catch (\Exception $e) {
                return $this->storeExceptionLog($e);
            }
        }

        $data = $request->only([
            'status',
            'parent',
            'family',
            'additional',
            'values',
            'super_attributes',
            'variant_structure',
            'variants',
            'associations',
        ]);

        try {
            $family = $this->findFamilyOr404($data['family']);
            $data['type'] = config('product_types.configurable.key');

            $structureCode = $data['variant_structure'] ?? null;

            unset($data['variant_structure']);

            if (filled($structureCode)) {
                $failure = $this->applyVariantStructureReference($data, $structureCode, $family->code, $family->id);

                if ($failure) {
                    return $failure;
                }
            }

            $this->validateSuperAttributes($data, $family);

            unset($data['family']);
            $data['attribute_family_id'] = $family->id;
            $data['sku'] = $this->getSkuFromValues($data);

            try {
                $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY]);
            } catch (ValidationException $e) {
                return $this->validateErrorResponse($e->validator->errors()->messages());
            }

            if (! empty($data['variants']) && is_array($data['variants'])) {
                try {
                    $data['variants'] = $this->normalizeVariantsPayload($data['variants']);
                } catch (ValidationException $e) {
                    return $this->validateErrorResponse($e->validator->errors()->messages());
                }
            }

            $this->validateRichAssociationsBeforeCreate($data);

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
     * Store a newly created variant_group resource under its parent configurable product.
     *
     * A structure may fix more than one axis at level 1, so a group is identified
     * among its siblings by the full ordered level-1 axis tuple: every level-1 code
     * must be supplied and uniqueness is checked on the whole tuple.
     */
    protected function storeVariantGroup(StoreConfigurableProductRequest $request): JsonResponse
    {
        $parent = $this->findParentProductOr404($request->input('parent'));

        if ($parent->type !== config('product_types.configurable.key') || (int) ($parent->variantStructure?->levels ?? 1) !== 2) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.products.product-not-found', ['sku' => $request->input('parent')])
            );
        }

        $common = $request->input('values.common', []);
        $sku = $common['sku'];
        $axisCodes = app(VariantStructurePlanner::class)->axisCodesByLevel($parent->variantStructure)['level_1'] ?? [];

        $missingAxisCodes = array_values(array_diff($axisCodes, array_keys($common)));

        if ($missingAxisCodes !== []) {
            return $this->validateErrorResponse([
                'axis' => [trans('admin::app.catalog.products.edit.types.configurable.supper-attribute-not-found', ['attribute' => implode(', ', $missingAxisCodes)])],
            ]);
        }

        $axisTuple = [];

        foreach ($axisCodes as $axisCode) {
            $axisTuple[$axisCode] = $common[$axisCode];
        }

        $node = DB::transaction(function () use ($parent, $sku, $axisTuple, $common) {
            $this->productRepository->getModel()::query()->whereKey($parent->id)->lockForUpdate()->first();

            if ($axisTuple !== [] && ! $this->productRepository->isUniqueVariantForProduct($parent->id, $axisTuple, null, '', 'variant_group')) {
                return null;
            }

            $groupValues = $common;
            unset($groupValues['sku']);

            return $parent->getTypeInstance()->createVariantGroup($parent, [
                'sku'          => $sku,
                'group_values' => $groupValues,
            ]);
        });

        if (! $node) {
            return $this->validateErrorResponse([
                'axis' => [trans('admin::app.catalog.products.edit.types.configurable.variant-given-exists', ['variants' => json_encode($axisTuple)])],
            ]);
        }

        return $this->successResponse(
            trans('admin::app.catalog.products.create-success'),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConfigurableProductRequest $request, string $sku): JsonResponse
    {
        $data = $request->only([
            'status',
            'parent',
            'additional',
            'values',
            'variants',
            'associations',
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

            if ($product->type === config('product_types.configurable.key')) {
                $data['super_attributes'] = $product->super_attributes->pluck('code')?->toArray();
                $data['variants'] = $this->setVaraints($product, $data, $data['sku']);
            }

            Event::dispatch('catalog.product.update.before', $id);

            $this->updateProduct($data, $product);

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
     */
    public function partialUpdate(PartialUpdateConfigurableProductRequest $request, string $sku): JsonResponse
    {
        $data = $request->only([
            'status',
            'additional',
            'values',
            'associations',
        ]);

        try {
            $product = $this->findProductOr404($sku);

            if (! empty($data[AbstractType::PRODUCT_VALUES_KEY])) {
                $this->valuesValidator->validateOnlyExistingSectionData(data: $data[AbstractType::PRODUCT_VALUES_KEY], productId: $product->id);
            }

            Event::dispatch('catalog.product.update.before', $product->id);

            $this->patchProduct($product, $data);

            return $this->successResponse(
                trans('admin::app.catalog.products.update-success'),
                Response::HTTP_OK,

            );
        } catch (ValidationException $e) {
            return $this->validateErrorResponse($e->validator->errors()->messages());
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified configurable product.
     */
    public function delete(string $code): JsonResponse
    {
        try {
            $product = $this->findProductOr404($code);

            if ($product->type === config('product_types.variant_group.key')) {
                $deleted = DB::transaction(function () use ($product) {
                    $this->productRepository->getModel()::query()->whereKey($product->id)->lockForUpdate()->first();

                    if ($product->variants()->exists()) {
                        return false;
                    }

                    Event::dispatch('catalog.product.delete.before', $product->sku);
                    $product->delete();
                    Event::dispatch('catalog.product.delete.after', $product->sku);

                    return true;
                });

                if (! $deleted) {
                    return $this->validateErrorResponse([
                        'children' => [trans('admin::app.catalog.products.edit.types.configurable.variant-group-has-children')],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => trans('admin::app.catalog.products.delete-success'),
                    'sku'     => $code,
                ], JsonResponse::HTTP_OK);
            }

            Event::dispatch('catalog.product.delete.before', $code);

            $product->delete();

            Event::dispatch('catalog.product.delete.after', $code);

            return response()->json([
                'success' => true,
                'message' => trans('admin::app.catalog.products.delete-success'),
                'sku'     => $product['sku'],
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Convert the API-facing `variants` payload as `[{sku, attributes:{attr1: val, attr2: val}}]`
     *
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<string, array<string, mixed>>
     *
     * @throws ValidationException
     */
    protected function normalizeVariantsPayload(array $variants): array
    {
        $validator = Validator::make(
            ['variants' => $variants],
            [
                'variants'              => ['array'],
                'variants.*'            => ['required', 'array'],
                'variants.*.sku'        => ['required', 'string'],
                'variants.*.attributes' => ['required', 'array'],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $normalized = [];

        foreach (array_values($variants) as $index => $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $sku = $variant['sku'] ?? null;
            $attributes = $variant['attributes'] ?? null;

            if (empty($sku) || ! is_array($attributes)) {
                continue;
            }

            $common = $attributes;
            $common['sku'] = $sku;

            $normalized['variant_'.$index] = [
                'sku'    => $sku,
                'values' => [
                    AbstractType::COMMON_VALUES_KEY => $common,
                ],
            ];
        }

        return $normalized;
    }
}
