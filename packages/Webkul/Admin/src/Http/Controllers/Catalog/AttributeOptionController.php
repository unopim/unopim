<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeOptionDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AttributeOptionForm;
use Webkul\Attribute\Enums\SwatchTypeEnum;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeOptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeOptionRepository $attributeOptionRepository,
        protected AttributeRepository $attributeRepository,
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
    public function store(int $attributeId, AttributeOptionForm $request): JsonResponse
    {
        $requestData = $request->get('locales');

        $requestData['attribute_id'] = $attributeId;

        $requestData['code'] = $request->get('code');

        $attribute = $this->attributeRepository->find($attributeId);

        if (in_array($attribute->swatch_type, SwatchTypeEnum::getValues(), true)) {
            $swatchValue = $request->file('swatch_value') ?? $request->get('swatch_value');

            if ($attribute->swatch_type === 'color' && blank($swatchValue)) {
                $swatchValue = '#000000';
            }

            $requestData['swatch_value'] = $swatchValue;
        }

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

        return new JsonResponse(['option' => $option]);
    }

    /**
     * Update attribute option
     */
    public function update(int $attributeId, int $id): JsonResponse
    {
        $this->validate(request(), ['locales.*.label' => 'nullable|string']);

        $requestData = request()->only('locales', 'swatch_value');
        Event::dispatch('catalog.attribute.option.update.before', $id);

        $option = $this->attributeOptionRepository->update(array_merge($requestData['locales'], [
            'swatch_value' => $requestData['swatch_value'] ?? '',
        ]), $id);

        Event::dispatch('catalog.attribute.option.update.after', $option);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.edit.option.update-success'),
        ]);
    }

    /**
     * Updates the sort order of an attribute option based on the direction it is moved (up or down).
     */
    public function updateSort(int $attributeId): JsonResponse
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
        try {
            Event::dispatch('catalog.attribute.option.delete.before', $id);

            $this->attributeOptionRepository->delete($id);

            Event::dispatch('catalog.attribute.option.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.edit.option.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
