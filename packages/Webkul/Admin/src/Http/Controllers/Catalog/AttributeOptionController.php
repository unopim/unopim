<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeOptionDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AttributeOptionForm;
use Webkul\Attribute\Repositories\AttributeOptionRepository;

class AttributeOptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeOptionRepository $attributeOptionRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(int $id)
    {
        return app(AttributeOptionDataGrid::class)->setAttributeId($id)->toJson();
    }

    /**
     * Store a newly created option.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(int $attributeId, AttributeOptionForm $request)
    {
        $requestData = $request->get('locales');

        $requestData['attribute_id'] = $attributeId;

        $requestData['code'] = $request->get('code');

        Event::dispatch('catalog.attribute.option.create.before', $requestData);

        try {
            $attribute = $this->attributeOptionRepository->create($requestData);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        Event::dispatch('catalog.attribute.option.create.after', $attribute);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.edit.option.create-success'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $attributeId, int $id): JsonResponse
    {
        $option = $this->attributeOptionRepository->find($id)->toArray();

        if (! $option) {
            abort(404);
        }

        foreach ($option['translations'] as $key => $translation) {
            $option['locales'][$translation['locale']] = $translation['label'] ?? '';
        }

        return new JsonResponse([
            'option' => $option,
        ]);
    }

    /**
     * Update attribute option
     */
    public function update(int $attributeId, int $id)
    {
        $this->validate(request(), ['locales.*.label' => 'nullable|string']);

        $requestData = request()->only('locales');

        Event::dispatch('catalog.attribute.option.update.before', $id);

        $option = $this->attributeOptionRepository->update($requestData['locales'], $id);

        Event::dispatch('catalog.attribute.option.update.after', $option);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.edit.option.update-success'),
        ]);
    }

    /**
     * Update attribute option
     */
    public function updateSort(int $attributeId)
    {
        $data = request()->all();

        $sortOrderUpdated = $this->attributeOptionRepository->updateSortOrder($data['optionIds'], $data['direction'], $data['toIndex'], $attributeId);

        if (! $sortOrderUpdated) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.edit.option.sort-update-failure'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.edit.option.sort-update-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $attributeId, int $id): JsonResponse
    {
        // TODO: add validation before delete to check if it is not being used in any product
        $attribute = $this->attributeOptionRepository->findOrFail($id);

        try {
            Event::dispatch('catalog.attribute.option.delete.before', $id);

            $this->attributeOptionRepository->delete($id);

            Event::dispatch('catalog.attribute.option.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.edit.option.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
