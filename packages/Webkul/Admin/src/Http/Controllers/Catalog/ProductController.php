<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Slug;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

class ProductController extends Controller
{
    /*
    * Using const variable for status
    */
    const ACTIVE_STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductRepository $productRepository,
        protected ProductValuesValidator $valuesValidator,
        protected ChannelRepository $channelRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ProductDataGrid::class)->toJson();
        }

        return view('admin::catalog.products.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $families = $this->attributeFamilyRepository->all();

        $configurableFamily = null;

        if ($familyId = request()->get('family')) {
            $configurableFamily = $this->attributeFamilyRepository->find($familyId);
        }

        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        if (request()->has('super_attributes')) {
            request()->merge([
                'super_attributes' => json_decode(request()->get('super_attributes'), true),
            ]);
        }

        $this->validate(request(), [
            'type'                => 'required',
            'attribute_family_id' => 'required',
            'sku'                 => ['required', 'unique:products,sku', new Slug],
            'super_attributes'    => 'array|min:1',
        ]);

        $data = request()->only([
            'type',
            'attribute_family_id',
            'sku',
            'super_attributes',
            'family',
        ]);

        if (
            ProductType::hasVariants($data['type'])
            && ! isset($data['super_attributes'])
        ) {
            $configurableFamily = $this->attributeFamilyRepository->find($data['attribute_family_id']);

            $configurableAttributes = [];

            foreach ($configurableFamily->getConfigurableAttributes() as $attribute) {
                $configurableAttributes[] = [
                    'code' => $attribute->code,
                    'name' => $attribute->name,
                    'id'   => $attribute->id,
                ];
            }

            if (empty($configurableAttributes)) {
                return new JsonResponse([
                    'errors' => [
                        'attribute_family_id' => [trans('admin::app.catalog.products.index.create.not-config-family-error')],
                    ],
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            return new JsonResponse([
                'data' => [
                    'attributes' => $configurableAttributes,
                ],
            ]);
        }

        Event::dispatch('catalog.product.create.before');

        $product = $this->productRepository->create($data);

        Event::dispatch('catalog.product.create.after', $product);

        session()->flash('success', trans('admin::app.catalog.products.create-success'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.products.edit', $product->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        return view('admin::catalog.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProductForm $request, int $id)
    {
        Event::dispatch('catalog.product.update.before', $id);

        $configurableValues = [];

        $data = $request->all();

        $product = $this->productRepository->find($id);

        foreach (($product?->parent?->super_attributes ?? []) as $attr) {
            $attrCode = $attr->code;

            $configurableValues[$attrCode] = $data['values']['common'][$attrCode];
        }

        if (! empty($configurableValues) && $product->parent_id) {
            $isUnique = $this->productRepository->isUniqueVariantForProduct(
                productId: $product->parent_id,
                configAttributes: $configurableValues,
                variantId: $id
            );

            if (! $isUnique) {
                session()->flash('warning', trans('admin::app.catalog.products.edit.types.configurable.create.variant-already-exists'));

                return back()->withInput();
            }
        }

        try {
            $this->valuesValidator->validate(data: $data[AbstractType::PRODUCT_VALUES_KEY], productId: $id);
        } catch (ValidationException $e) {
            $messages = [];

            foreach ($e->validator->errors()->messages() as $key => $message) {
                $messageKey = str_replace('.', '][', $key);

                $messageKey = AbstractType::PRODUCT_VALUES_KEY.'['.$messageKey.']';

                $messages[$messageKey] = $message;
            }

            $e = $e::withMessages($messages);

            Log::debug($e);

            session()->flash('error', trans('admin::app.catalog.products.update-failure'));

            throw $e;
        }

        $product = $this->productRepository->update($data, $id);

        Event::dispatch('catalog.product.update.after', $product);

        session()->flash('success', trans('admin::app.catalog.products.update-success'));

        return redirect()->route('admin.catalog.products.edit', [
            'id'      => $id,
            'channel' => core()->getRequestedChannelCode(),
            'locale'  => core()->getRequestedLocaleCode(),
        ]);
    }

    /**
     * Copy a given Product.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(int $id)
    {
        try {
            $product = $this->productRepository->copy($id);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }

        session()->flash('success', trans('admin::app.catalog.products.product-copied'));

        return new JsonResponse([
            'redirect_url' => route('admin.catalog.products.edit', $product->id),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Event::dispatch('catalog.product.delete.before', $id);

            $this->productRepository->delete($id);

            Event::dispatch('catalog.product.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.delete-failed'),
        ], 500);
    }

    /**
     * Mass delete the products.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $productIds = $massDestroyRequest->input('indices');

        try {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->find($productId);

                if (isset($product)) {
                    Event::dispatch('catalog.product.delete.before', $productId);

                    $this->productRepository->delete($productId);

                    Event::dispatch('catalog.product.delete.after', $productId);
                }
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass update the products.
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $data = $massUpdateRequest->all();

        $productIds = $data['indices'];

        foreach ($productIds as $productId) {
            Event::dispatch('catalog.product.update.before', $productId);

            $product = $this->productRepository->updateStatus($massUpdateRequest->input('value'), $productId);

            Event::dispatch('catalog.product.update.after', $product);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success'),
        ], 200);
    }

    /**
     * To be manually invoked when data is seeded into products.
     *
     * @return \Illuminate\Http\Response
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $results = [];

        request()->query->add([
            'status'               => null,
            'visible_individually' => null,
            'name'                 => request('query'),
            'sort'                 => 'created_at',
            'order'                => 'desc',
            'skipSku'              => request('skipSku'),
        ]);

        $products = $this->productRepository->searchFromDatabase();

        foreach ($products as $product) {
            $results[] = $product->normalizeWithImage();
        }

        $products->setCollection(collect($results));

        return response()->json($products);
    }

    /**
     * Check variant configurable attributes uniqueness
     */
    public function checkVariantUniqueness(): JsonResponse
    {
        $variantAttributes = request()->get('variantAttributes');

        $data = request()->except('variantAttributes');

        $isUnique = $this->productRepository->isUniqueVariantForProduct($data['parentId'], $variantAttributes, $data['sku'], $data['variantId'] ?? null);

        if (! $isUnique) {
            return new JsonResponse([
                'errors' => [
                    'message' => trans('admin::app.catalog.products.edit.types.configurable.variant-exists'),
                ],
            ]);
        }

        return new JsonResponse([]);
    }

    public function getLocale(): JsonResponse
    {
        $channel = request()->channel;
        $result = $this->channelRepository->findOneByField('code', $channel);
        $locales = $result->locales()->select('locales.code')->get();
        $options = [];
        foreach ($locales as $locale) {
            $options[] = [
                'id'    => $locale->code,
                'label' => $locale->name,
            ];
        }

        return new JsonResponse([
            'locales' => $options,
        ]);
    }

    public function getAttribute(): JsonResponse
    {
        $product = $this->productRepository->findByField('id', request()->productId)->first();
        $attributes = $product->getEditableAttributes()->where('ai_translate', 1)->select('code', 'type', 'ai_translate');
        $attributeOptions = [];
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $attributeOptions[] = [
                    'id'    => $attribute['code'],
                    'label' => $attribute['code'],
                ];
            }
        }

        return new JsonResponse([
            'attributes' => $attributeOptions,
        ]);
    }
}
