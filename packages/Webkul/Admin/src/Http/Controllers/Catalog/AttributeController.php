<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Enums\SwatchTypeEnum;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Repositories\ProductRepository;

class AttributeController extends Controller
{
    public const AI_TRANSLATE_ENABLED = '1';

    public const AI_TRANSLATE_DISABLED = '0';

    public const VALUE_PER_LOCALE_ENABLED = 1;

    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AttributeDataGrid::class)->toJson();
        }

        return view('admin::catalog.attributes.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $locales = $this->localeRepository->getActiveLocales();

        $swatchTypes = SwatchTypeEnum::getValues();

        return view('admin::catalog.attributes.create', compact('locales', 'swatchTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'code'        => ['required', 'not_in:type,attribute_family_id', 'unique:attributes,code', new Code, new NotSupportedAttributes],
            'type'        => 'required',
            'swatch_type' => [
                'nullable',
                new SwatchTypes,
                function ($attribute, $value, $fail) {
                    if (! in_array(request('type'), ['select', 'multiselect']) && ! is_null($value)) {
                        $fail(trans('validation.null', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);

        $requestData = request()->all();
        $requestData['ai_translate'] = (isset($requestData['value_per_locale']) && $requestData['value_per_locale'] == self::VALUE_PER_LOCALE_ENABLED &&
            in_array($requestData['type'], [self::TEXT, self::TEXTAREA])) ? self::AI_TRANSLATE_ENABLED : self::AI_TRANSLATE_DISABLED;

        Event::dispatch('catalog.attribute.create.before');

        $attribute = $this->attributeRepository->create($requestData);

        Event::dispatch('catalog.attribute.create.after', $attribute);

        session()->flash('success', trans('admin::app.catalog.attributes.create-success'));

        return redirect()->route('admin.catalog.attributes.edit', $attribute->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $attribute = $this->attributeRepository->findOrFail($id);

        $locales = $this->localeRepository->getActiveLocales();

        $swatchTypes = SwatchTypeEnum::getValues();

        return view('admin::catalog.attributes.edit', compact('attribute', 'locales', 'swatchTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), [
            'code'        => ['required', 'unique:attributes,code,'.$id, new Code],
            'type'        => 'required',
            'swatch_type' => [
                'nullable',
                new SwatchTypes,
                function ($attribute, $value, $fail) {
                    if (! in_array(request('type'), ['select', 'multiselect']) && ! is_null($value)) {
                        $fail(trans('core::validation.not-supported', ['attribute' => $attribute, 'unsupported' => request('type')]));
                    }
                },
            ],
        ]);

        $requestData = request()->except(['type', 'code', 'value_per_locale', 'value_per_channel', 'is_unique']);
        if (! array_key_exists('ai_translate', $requestData)) {
            $requestData['ai_translate'] = self::AI_TRANSLATE_DISABLED;
        }

        Event::dispatch('catalog.attribute.update.before', $id);

        $attribute = $this->attributeRepository->update($requestData, $id);

        Event::dispatch('catalog.attribute.update.after', $attribute);

        session()->flash('success', trans('admin::app.catalog.attributes.update-success'));

        return redirect()->route('admin.catalog.attributes.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $attribute = $this->attributeRepository->findOrFail($id);

        if (! $attribute->canBeDeleted()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.index.datagrid.delete-failed'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($this->attributeCanBeDeleted($id) > 0) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.index.datagrid.delete-attribute-failure'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('catalog.attribute.delete.before', $id);

            $this->attributeRepository->delete($id);

            Event::dispatch('catalog.attribute.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * attribute can't be deleted for super attributes
     */
    public function attributeCanBeDeleted(int $id): int
    {
        return DB::table('product_super_attributes')
            ->where('attribute_id', $id)
            ->count();
    }

    /**
     * Remove the specified resources from database.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');
        $delete = false;

        foreach ($indices as $index) {
            Event::dispatch('catalog.attribute.delete.before', $index);

            $attribute = $this->attributeRepository->find($index);

            if (! $attribute->canBeDeleted()) {
                continue;
            }

            $this->attributeRepository->delete($index);
            $delete = true;

            Event::dispatch('catalog.attribute.delete.after', $index);
        }

        if (! $delete) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-failed'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
        ]);
    }

    /**
     * Get super attributes of product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function productSuperAttributes(int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        $superAttributes = $this->productRepository->getSuperAttributes($product);

        return response()->json([
            'data'  => $superAttributes,
        ]);
    }
}
