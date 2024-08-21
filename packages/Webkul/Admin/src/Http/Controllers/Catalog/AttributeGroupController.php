<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeGroupDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Repositories\ProductRepository;

class AttributeGroupController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeGroupRepository $attributeGroupRepository,
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
            return app(AttributeGroupDataGrid::class)->toJson();
        }

        return view('admin::catalog.attributegroups.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::catalog.attributegroups.create', ['locales' => $this->localeRepository->getActiveLocales()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_groups,code', new Code],
        ]);

        $requestData = request()->all();

        Event::dispatch('catalog.attribute.group.create.before');

        $attributeGroup = $this->attributeGroupRepository->create($requestData);

        Event::dispatch('catalog.attribute.group.create.after', $attributeGroup);

        session()->flash('success', trans('admin::app.catalog.attribute-groups.create-success'));

        return redirect()->route('admin.catalog.attribute.groups.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $attributeGroup = $this->attributeGroupRepository->findOrFail($id);

        $locales = $this->localeRepository->getActiveLocales();

        return view('admin::catalog.attributegroups.edit', compact('attributeGroup', 'locales'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_groups,code,'.$id, new Code],
        ]);

        $requestData = request()->except(['code']);

        Event::dispatch('catalog.attribute.group.update.before', $id);

        $attributeGroup = $this->attributeGroupRepository->update($requestData, $id);

        Event::dispatch('catalog.attributegroup.update.after', $attributeGroup);

        session()->flash('success', trans('admin::app.catalog.attribute-groups.update-success'));

        return redirect()->route('admin.catalog.attribute.groups.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $attributeGroup = $this->attributeGroupRepository->findOrFail($id);

        if ($attributeGroup->groupMappings()->count()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attribute-groups.attribute-group-error'),
            ], 400);
        }

        try {
            Event::dispatch('catalog.attribute.group.delete.before', $id);

            $this->attributeGroupRepository->delete($id);

            Event::dispatch('catalog.attribute.group.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attribute-groups.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
