<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\AttributeFamilyDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;

class AttributeFamilyController extends Controller
{
    const DEFAULT_GROUP = 'general';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(AttributeFamilyDataGrid::class)->toJson();
        }

        return view('admin::catalog.families.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $normalizedData = $this->normalize();

        return view('admin::catalog.families.create', $normalizedData);
    }

    /**
     * Normalize attribute family, custom attributes, and custom attribute groups data.
     *
     * @return array
     */
    private function normalize($attributeFamily = null)
    {
        $familyGroupMappings = $attributeFamily?->attributeFamilyGroupMappings()->with('attributeGroups')->get()->map(function ($familyGroupMapping) {
            $attributeGroup = $familyGroupMapping->attributeGroups->first();

            $customAttributes = $attributeGroup->customAttributes($familyGroupMapping->attribute_family_id)->map(function ($attribute) use ($attributeGroup) {
                $attributeArray = $attribute->toArray();

                return [
                    'id'       => $attributeArray['id'],
                    'code'     => $attributeArray['code'],
                    'group_id' => $attributeGroup->id,
                    'name'     => ! empty($attributeArray['name']) ? $attributeArray['name'] : '['.$attributeArray['code'].']',
                    'type'     => $attributeArray['type'],
                ];
            })->toArray();

            $attributeGroup = $attributeGroup?->toArray() ?? [];

            return [
                'id'               => $attributeGroup['id'],
                'code'             => $attributeGroup['code'],
                'group_mapping_id' => $familyGroupMapping->id,
                'name'             => ! empty($attributeGroup['name']) ? $attributeGroup['name'] : '['.$attributeGroup['code'].']',
                'customAttributes' => $customAttributes,
            ];
        })->toArray();

        return [
            'locales'         => $this->localeRepository->getActiveLocales(),
            'attributeFamily' => [
                'family'              => $attributeFamily,
                'familyGroupMappings' => $familyGroupMappings ?? [],
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code', new Code],
        ]);

        $requestData = request()->all();
        Event::dispatch('catalog.attribute_family.create.before');

        $attributeFamily = $this->attributeFamilyRepository->create($requestData);

        Event::dispatch('catalog.attribute_family.create.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.create-success'));

        return redirect()->route('admin.catalog.families.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if (request()->has('history')) {
            return view('admin::catalog.families.edit');
        }

        $isCompletenessTab = request()->has('completeness');

        $normalizedData = $isCompletenessTab
            ? ['attributeFamilyId' => $id]
            : $this->normalize($attributeFamily);

        $allChannels = $isCompletenessTab
            ? $this->channelRepository->getChannelAsOptions()->toJson()
            : '[]';

        return view('admin::catalog.families.edit', [
            ...$normalizedData,
            'allChannels' => $allChannels,
        ]);
    }

    /**
     * Show the form for copy the specified resource.
     */
    public function copy(int $id): View
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id, ['*']);
        $normalizedData = $this->normalize($attributeFamily);

        return view('admin::catalog.families.create', $normalizedData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): RedirectResponse
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code,'.$id, new Code],
        ]);

        $requestData = request()->except(['code']);

        Event::dispatch('catalog.attribute_family.update.before', $id);

        $attributeFamily = $this->attributeFamilyRepository->update($requestData, $id);

        Event::dispatch('catalog.attribute_family.update.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.update-success'));

        return redirect()->route('admin.catalog.families.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if ($attributeFamily->products()->count()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.families.attribute-product-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('catalog.attribute_family.delete.before', $id);

            $this->attributeFamilyRepository->delete($id);

            Event::dispatch('catalog.attribute_family.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.families.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.families.delete-failed', ['name' => 'admin::app.catalog.families.family']),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Check product has variant products and return family if exists
     */
    protected function hasVariantProducts($id): ?AttributeFamily
    {
        $family = $this->attributeFamilyRepository->find($id);

        $products = $family->products()->where('parent_id', '<>', null)->count();

        if ($products) {
            return $family;
        }

        return null;
    }
}
