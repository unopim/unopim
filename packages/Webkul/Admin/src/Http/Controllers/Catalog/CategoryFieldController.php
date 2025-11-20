<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\CategoryFieldDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Rules\NotSupportedFields;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;

class CategoryFieldController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryRepository $categoryRepository,
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
            return app(CategoryFieldDataGrid::class)->toJson();
        }

        return view('admin::catalog.categories.field.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::catalog.categories.field.create', ['locales' => $this->localeRepository->getActiveLocales()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'code'     => ['required', 'unique:category_fields,code', new Code, new NotSupportedFields],
            'type'     => 'required',
            'status'   => 'required',
            'position' => 'required|min:0',
            'section'  => 'required',
        ]);

        $requestData = request()->all();

        Event::dispatch('catalog.category_field.create.before');

        $attribute = $this->categoryFieldRepository->create($requestData);

        Event::dispatch('catalog.category_field.create.after', $attribute);

        session()->flash('success', trans('admin::app.catalog.category_fields.create-success'));

        return redirect()->route('admin.catalog.category_fields.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        return view('admin::catalog.categories.field.edit', [
            'categoryField' => $this->categoryFieldRepository->findOrFail($id),
            'locales'       => $this->localeRepository->getActiveLocales(),
        ]);
    }

    /**
     * Get attribute options associated with attribute.
     *
     * @return \Illuminate\View\View
     */
    public function getCategoryFieldOptions(int $id)
    {
        $categoryField = $this->categoryFieldRepository->findOrFail($id);

        return $categoryField->options()->orderBy('sort_order')->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), [
            'code'     => ['required', 'unique:category_fields,code,'.$id, new Code],
            'type'     => 'required',
            'status'   => 'required',
            'position' => 'required',
            'section'  => 'required',
        ]);

        $requestData = request()->except(['code', 'type', 'value_per_locale', 'is_unique']);

        Event::dispatch('catalog.category_field.update.before', $id);

        $categoryField = $this->categoryFieldRepository->update($requestData, $id);

        Event::dispatch('catalog.category_field.update.after', $categoryField);

        session()->flash('success', trans('admin::app.catalog.category_fields.update-success'));

        return redirect()->route('admin.catalog.category_fields.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryFieldRepository->findOrFail($id);

        if (! $category->canBeDeleted()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.category_fields.index.datagrid.delete-failed'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('catalog.category_field.delete.before', $id);

            $this->categoryFieldRepository->delete($id);

            Event::dispatch('catalog.category_field.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.category_fields.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.category_fields.delete-failed'),
        ], 500);
    }

    /**
     * Remove the specified resources from database.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');
        $delete = false;

        foreach ($indices as $index) {
            Event::dispatch('catalog.category_field.delete.before', $index);

            $category = $this->categoryFieldRepository->find($index);

            if (! $category->canBeDeleted()) {
                continue;
            }

            $this->categoryFieldRepository->delete($index);
            $delete = true;

            Event::dispatch('catalog.category_field.delete.after', $index);
        }

        if (! $delete) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-delete-failed'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-delete-success'),
        ]);
    }

    /**
     * Updates the status of category fields
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $categoryFieldIds = $massUpdateRequest->input('indices');

        $value = $massUpdateRequest->input('value');

        foreach ($categoryFieldIds as $fieldId) {
            try {
                Event::dispatch('catalog.category_field.update.before', $fieldId);

                $this->categoryFieldRepository->update([
                    'status' => $value,
                ], $fieldId);

                Event::dispatch('catalog.category_field.update.after', $fieldId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-update-success'),
        ], JsonResponse::HTTP_OK);
    }
}
