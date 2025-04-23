<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeFamilyDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;

class AttributeFamilyController extends Controller
{
    const DEFAULT_GROUP = 'general';

    protected array $usedAttributes = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
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
        // $customAttributes = []; // $this->attributeRepository->limit(10);
        // $customAttributeGroups = $this->attributeGroupRepository->all(['id', 'code']);

        $currentLocale = core()->getRequestedLocaleCode();

        $variantOptionAttributes = [];

        // @TODO: Need to improve this function
        $familyGroupMappings = $attributeFamily?->attributeFamilyGroupMappings->map(function ($familyGroupMapping) {
            $attributeGroup = $familyGroupMapping->attributeGroups->first();

            $customAttributes = $attributeGroup->customAttributes($familyGroupMapping->attribute_family_id)->map(function ($attribute) use ($attributeGroup) {
                $this->usedAttributes[] = $attribute->code;

                return [
                    'id'              => $attribute->id,
                    'code'            => $attribute->code,
                    'group_id'        => $attributeGroup->id,
                    'name'            => ! empty($attribute->name) ? $attribute->name : $attribute->code,
                    'type'            => $attribute->type,
                    'is_configurable' => (! $attribute->isLocaleBasedAttribute() && ! $attribute->isChannelBasedAttribute()),
                ];
            })->toArray();

            return [
                'id'               => $attributeGroup->id,
                'code'             => $attributeGroup->code,
                'group_mapping_id' => $familyGroupMapping->id,
                'name'             => ! empty($attributeGroup->name) ? $attributeGroup->name : $attributeGroup->code,
                'customAttributes' => $customAttributes,
            ];
        })->toArray();

        // // Normalize custom attributes data
        // $normalizedCustomAttributes = [];

        // foreach ($customAttributes as $customAttribute) {
        //     if (! in_array($customAttribute->code, $this->usedAttributes)) {
        //         $this->usedAttributes[] = $customAttribute->code;

        //         $normalizedCustomAttributes[] = [
        //             'id'              => $customAttribute->id,
        //             'code'            => $customAttribute->code,
        //             'type'            => $customAttribute->type,
        //             'name'            => ! empty($customAttribute->name) ? $customAttribute->name : $customAttribute->code,
        //             'is_configurable' => (! $customAttribute->isLocaleBasedAttribute() && ! $customAttribute->isChannelBasedAttribute()),
        //         ];
        //     } else {
        //         if (
        //             in_array($customAttribute->type, AttributeFamily::ALLOWED_VARIANT_OPTION_TYPES)
        //             && ! $customAttribute->isLocaleBasedAttribute()
        //             && ! $customAttribute->isChannelBasedAttribute()
        //         ) {
        //             $variantOptionAttributes[] = [
        //                 'id'   => $customAttribute->id,
        //                 'code' => $customAttribute->code,
        //                 'name' => ! empty($customAttribute->name) ? $customAttribute->name : "[{$customAttribute->code}]",
        //             ];
        //         }
        //     }
        // }

        // // Normalize custom attribute groups data
        // $normalizedCustomAttributeGroups = [];
        // foreach ($customAttributeGroups as $customAttributeGroup) {
        //     if (empty($familyGroupMappings) && $customAttributeGroup->code === self::DEFAULT_GROUP) {
        //         $familyGroupMappings[] = [
        //             'id'               => $customAttributeGroup->id,
        //             'code'             => $customAttributeGroup->code,
        //             'name'             => ! empty($customAttributeGroup->name) ? $customAttributeGroup->name : $customAttributeGroup->code,
        //             'customAttributes' => [],
        //         ];
        //     }

        //     $normalizedCustomAttributeGroups[] = [
        //         'id'               => $customAttributeGroup->id,
        //         'code'             => $customAttributeGroup->code,
        //         'name'             => ! empty($customAttributeGroup->name) ? $customAttributeGroup->name : $customAttributeGroup->code,
        //         'customAttributes' => [],
        //     ];
        // }

        // Normalize attribute family data
        $normalizedAttributeFamily = [
            'family'              => $attributeFamily,
            'familyGroupMappings' => $familyGroupMappings ?? [],
        ];

        $locales = $this->localeRepository->getActiveLocales();

        return [
            'attributeFamily'         => $normalizedAttributeFamily,
            'locales'                 => $locales,
            // 'customAttributes'        => $normalizedCustomAttributes,
            // 'customAttributeGroups'   => $normalizedCustomAttributeGroups,
            'variantOptionAttributes' => $variantOptionAttributes,
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
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id, ['*']);
        $normalizedData = $this->normalize($attributeFamily);

        return view('admin::catalog.families.edit', $normalizedData);
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
