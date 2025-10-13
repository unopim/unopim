<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AttributeFamilyDataGrid::class)->toJson();
        }

        return view('admin::catalog.families.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
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
        $driver = DB::getDriverName();

        $familyGroupMappings = $attributeFamily?->attributeFamilyGroupMappings()
            ->with(['attributeGroups' => function ($query) {
                $query->select(
                    'attribute_groups.id as attribute_group_id',
                    'attribute_groups.code',
                    DB::raw('COALESCE(attribute_group_translations.name, attribute_groups.code) as attribute_group_name')
                )
                    ->leftJoin(
                        'attribute_group_translations',
                        'attribute_group_translations.attribute_group_id',
                        '=',
                        'attribute_groups.id'
                    )
                    ->groupBy('attribute_groups.id', 'attribute_groups.code', 'attribute_group_translations.name');
            }])
            ->get()
            ->map(function ($familyGroupMapping) {
                $attributeGroup = $familyGroupMapping->attributeGroups->first();

                $customAttributes = $attributeGroup
                    ? $attributeGroup->customAttributes($familyGroupMapping->attribute_family_id)
                        ->map(function ($attribute) use ($attributeGroup) {
                            $attributeArray = $attribute->toArray();

                            return [
                                'id'       => $attributeArray['id'],
                                'code'     => $attributeArray['code'],
                                'group_id' => $attributeGroup->attribute_group_id,
                                'name'     => ! empty($attributeArray['name'])
                                    ? $attributeArray['name']
                                    : '['.$attributeArray['code'].']',
                                'type'     => $attributeArray['type'],
                            ];
                        })->toArray()
                    : [];

                $attributeGroup = $attributeGroup?->toArray() ?? [];

                return [
                    'id'               => $attributeGroup['attribute_group_id'] ?? null,
                    'code'             => $attributeGroup['code'] ?? null,
                    'group_mapping_id' => $familyGroupMapping->id,
                    'name'             => $attributeGroup['attribute_group_name'] ?? ('['.($attributeGroup['code'] ?? '').']'),
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
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
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
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
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
            : [];

        return view('admin::catalog.families.edit', [
            ...$normalizedData,
            'allChannels' => $allChannels,
        ]);
    }

    /**
     * Show the form for copy the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function copy(int $id)
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id, ['*']);
        $normalizedData = $this->normalize($attributeFamily);

        return view('admin::catalog.families.create', $normalizedData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
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
            ], 400);
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
        ], 500);
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
