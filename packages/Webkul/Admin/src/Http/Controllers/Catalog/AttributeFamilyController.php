<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\AttributeFamilyDataGrid;
use Webkul\Admin\DataGrids\Catalog\VariantStructureDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;

class AttributeFamilyController extends Controller
{
    const GROUPS_PER_PAGE = 25;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(AttributeFamilyDataGrid::class)->toJson();
        }

        return view('admin::catalog.families.index', [
            'families' => $this->attributeFamilyRepository->getOptions(),
        ]);
    }

    /**
     * Get one page of a family's assigned groups.
     */
    public function groups(int $id): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        $page = $this->groupPage(
            $attributeFamily,
            max(1, (int) request()->input('page', 1)),
            trim((string) request()->input('query', ''))
        );

        return new JsonResponse($page);
    }

    /**
     * Get the attributes assigned to one of a family's groups.
     */
    public function groupAttributes(int $id, int $groupId): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        $attributes = $attributeFamily
            ->attributeSummariesByGroup(core()->getRequestedLocaleCode(), $groupId)
            ->get($groupId, collect())
            ->map(fn ($attribute): array => [
                'id'       => $attribute->id,
                'code'     => $attribute->code,
                'group_id' => $groupId,
                'name'     => ! empty($attribute->name) ? $attribute->name : '['.$attribute->code.']',
                'position' => $attribute->position,
                'type'     => $attribute->type,
            ])
            ->values()
            ->all();

        return new JsonResponse(['attributes' => $attributes]);
    }

    /**
     * Normalize attribute family, custom attributes, and custom attribute groups data.
     */
    private function normalize($attributeFamily = null)
    {
        $page = $attributeFamily
            ? $this->groupPage($attributeFamily, 1)
            : ['groups' => [], 'total' => 0, 'lastPage' => 1];

        return [
            'locales'         => $this->localeRepository->getActiveLocales(),
            'attributeFamily' => [
                'family'              => $attributeFamily,
                'familyGroupMappings' => $page['groups'],
                'groupsTotal'         => $page['total'],
                'groupsLastPage'      => $page['lastPage'],
                'groupsPerPage'       => self::GROUPS_PER_PAGE,
                'groupMappingIds'     => $attributeFamily?->attributeFamilyGroupMappings()->toBase()->pluck('id')->all() ?? [],
                'variantStructures'   => $attributeFamily ? $this->variantStructuresPayload($attributeFamily) : [],
            ],
        ];
    }

    /**
     * Build one page of a family's groups for the editor.
     *
     * @return array{groups: array<int, array<string, mixed>>, total: int, lastPage: int}
     */
    private function groupPage(AttributeFamily $attributeFamily, int $page, string $search = ''): array
    {
        $localeCode = core()->getRequestedLocaleCode();

        $attributeCounts = $attributeFamily->attributeCountsByGroup();

        $result = $attributeFamily->paginateGroupSummaries($localeCode, $page, self::GROUPS_PER_PAGE, $search);

        return [
            'groups' => $result['groups']->map(fn ($group): array => [
                'id'               => $group->id,
                'code'             => $group->code,
                'group_mapping_id' => $group->group_mapping_id,
                'name'             => ! empty($group->name) ? $group->name : '['.$group->code.']',
                'position'         => $group->position,
                'attributesCount'  => $attributeCounts[$group->id] ?? 0,
                'attributesLoaded' => false,
                'customAttributes' => [],
                'hide'             => true,
            ])->values()->all(),
            'total'    => $result['total'],
            'lastPage' => $result['lastPage'],
        ];
    }

    /**
     * Get variant structures payload for the family UI.
     */
    protected function variantStructuresPayload(AttributeFamily $attributeFamily, ?int $structureId = null): array
    {
        $query = VariantStructure::query()
            ->where('attribute_family_id', $attributeFamily->id)
            ->with(['axes.attribute', 'placements.attribute']);

        if ($structureId) {
            $query->where('id', $structureId);
        }

        return $query->orderBy('id')
            ->get()
            ->map(function (VariantStructure $structure) {
                return [
                    'id'         => $structure->id,
                    'code'       => $structure->code,
                    'name'       => $structure->name ?: $structure->code,
                    'levels'     => $structure->levels,
                    'axes'       => [
                        'level_1' => $structure->axes
                            ->where('level', 'level_1')
                            ->map(fn ($axis) => $axis->attribute?->code)
                            ->filter()
                            ->values()
                            ->all(),
                        'level_2' => $structure->axes
                            ->where('level', 'level_2')
                            ->map(fn ($axis) => $axis->attribute?->code)
                            ->filter()
                            ->values()
                            ->all(),
                    ],
                    'placements' => [
                        'common'     => $structure->placements->where('level', 'common')->map(fn ($placement) => $placement->attribute?->code)->filter()->values()->all(),
                        'sub_parent' => $structure->placements->where('level', 'sub_parent')->map(fn ($placement) => $placement->attribute?->code)->filter()->values()->all(),
                        'variant'    => $structure->placements->where('level', 'variant')->map(fn ($placement) => $placement->attribute?->code)->filter()->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        $locale = core()->getRequestedLocaleCode();

        $this->validate(request(), [
            'code'              => ['required', 'unique:attribute_families,code', new Code],
            $locale.'.name'     => ['nullable', 'string'],
            'based_on'          => ['nullable', 'integer', 'exists:attribute_families,id'],
        ]);

        Event::dispatch('catalog.attribute_family.create.before');

        $attributeFamily = $this->attributeFamilyRepository->createScaffolded(
            request()->input('code'),
            request()->filled('based_on') ? (int) request()->input('based_on') : null,
            [
                $locale => [
                    'name' => request()->input($locale.'.name'),
                ],
            ],
        );

        Event::dispatch('catalog.attribute_family.create.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.create-success'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.families.edit', $attributeFamily->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        $activeTab = match (true) {
            request()->has('variants')     => 'variants',
            request()->has('completeness') => 'completeness',
            request()->has('history')      => 'history',
            default                        => 'general',
        };

        if ($activeTab === 'history') {
            return view('admin::catalog.families.edit');
        }

        if ($activeTab === 'completeness' && bouncer()->hasPermission('catalog.families.completeness')) {
            return view('admin::catalog.families.edit', [
                'attributeFamilyId' => $id,
                'allChannels'       => $this->channelRepository->getChannelAsOptions()->toJson(),
            ]);
        }

        return view('admin::catalog.families.edit', [
            ...$this->normalize($attributeFamily),
            'allChannels' => '[]',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): RedirectResponse
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code,'.$id, new Code],
        ]);

        $requestData = request()->except(['code', '_attribute_groups_dirty']);

        Event::dispatch('catalog.attribute_family.update.before', $id);

        try {
            $attributeFamily = DB::transaction(function () use ($requestData, $id) {
                $family = $this->attributeFamilyRepository->update($requestData, $id);

                $this->guardVariantStructureAxes($family);

                return $family;
            });
        } catch (ValidationException $e) {
            return redirect()->route('admin.catalog.families.edit', $id)->withErrors($e->errors());
        }

        Event::dispatch('catalog.attribute_family.update.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.update-success'));

        return redirect()->route('admin.catalog.families.edit', $id);
    }

    /**
     * Get variant structures for an attribute family.
     */
    public function variantStructures(int $id): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if (request()->boolean('datagrid')) {
            return app()->make(VariantStructureDataGrid::class, [
                'familyId' => $attributeFamily->id,
            ])->toJson();
        }

        if (request()->filled('structure_id')) {
            return new JsonResponse([
                'data' => $this->variantStructuresPayload($attributeFamily, (int) request()->input('structure_id'))[0] ?? null,
            ]);
        }

        return new JsonResponse([
            'data' => $this->variantStructuresPayload($attributeFamily),
        ]);
    }

    /**
     * Show variant structure ownership editor.
     */
    public function editVariantStructure(int $id, int $structureId): View
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);
        $structure = $this->variantStructuresPayload($attributeFamily, $structureId)[0] ?? null;

        abort_if(! $structure, 404);

        return view('admin::catalog.families.variant-structure-edit', [
            'attributeFamily' => $attributeFamily,
            'axisOptions'     => $this->variantAxisOptions($attributeFamily),
            'variantGroups'   => $this->variantAttributeGroups($attributeFamily),
            'structure'       => $structure,
        ]);
    }

    /**
     * Save variant structures for an attribute family.
     */
    public function saveVariantStructures(int $id): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if (request()->has('structure')) {
            return $this->saveVariantStructure($attributeFamily, request()->input('structure'));
        }

        $this->validate(request(), [
            'structures'                   => ['array'],
            'structures.*.code'            => ['required', new Code],
            'structures.*.name'            => ['nullable', 'string'],
            'structures.*.levels'          => ['required', 'integer', 'in:1,2'],
            'structures.*.axes'            => ['required', 'array'],
            'structures.*.axes.level_1'    => ['required', 'array'],
            'structures.*.axes.level_1.*'  => ['required', 'string'],
            'structures.*.axes.level_2'    => ['array'],
            'structures.*.axes.level_2.*'  => ['string'],
            'structures.*.placements'      => ['array'],
            'structures.*.placements.*'    => ['array'],
            'structures.*.placements.*.*'  => ['string'],
        ]);

        $structures = request()->input('structures', []);
        $familyAttributes = $attributeFamily->customAttributes()->get()->keyBy('code');
        $configurableAttributes = $attributeFamily->getConfigurableAttributes()->keyBy('code');
        $seenStructureCodes = [];

        foreach ($structures as $index => $structure) {
            $structures[$index]['placements'] = $this->forceUniqueAttributesToVariant($structure['placements'] ?? [], $familyAttributes);
            $structure = $structures[$index];

            $code = $structure['code'];

            if (in_array($code, $seenStructureCodes, true)) {
                abort(422, trans('validation.distinct', ['attribute' => "structures.$index.code"]));
            }

            $seenStructureCodes[] = $code;

            $levels = (int) $structure['levels'];
            $axes = $this->normalizeVariantAxes($structure['axes'] ?? []);
            $activeAxes = $levels === 2
                ? [...$axes['level_1'], ...$axes['level_2']]
                : $axes['level_1'];

            if (! count($axes['level_1']) || ($levels === 2 && ! count($axes['level_2']))) {
                abort(422, trans('validation.required', ['attribute' => "structures.$index.axes"]));
            }

            if (count(array_unique($activeAxes)) !== count($activeAxes)) {
                abort(422, trans('validation.distinct', ['attribute' => "structures.$index.axes"]));
            }

            foreach ($activeAxes as $axisCode) {
                if (! $configurableAttributes->has($axisCode)) {
                    abort(422, trans('validation.exists', ['attribute' => $axisCode]));
                }
            }

            foreach (($structure['placements'] ?? []) as $level => $codes) {
                if (! in_array($level, ['common', 'sub_parent', 'variant'], true)) {
                    abort(422, trans('validation.in', ['attribute' => $level]));
                }

                if ($levels === 1 && $level === 'sub_parent' && count($codes)) {
                    abort(422, trans('validation.in', ['attribute' => $level]));
                }

                foreach ($codes as $attributeCode) {
                    if (! $familyAttributes->has($attributeCode) || in_array($attributeCode, $activeAxes, true)) {
                        abort(422, trans('validation.exists', ['attribute' => $attributeCode]));
                    }
                }
            }
        }

        $previousStructures = VariantStructure::query()
            ->with(['axes.attribute', 'placements.attribute'])
            ->where('attribute_family_id', $attributeFamily->id)
            ->get();

        $previousSnapshots = $previousStructures
            ->mapWithKeys(fn (VariantStructure $structure): array => [
                $structure->code => $this->variantStructureSnapshot($structure),
            ])
            ->all();

        DB::transaction(function () use ($attributeFamily, $structures, $familyAttributes) {
            VariantStructure::query()
                ->where('attribute_family_id', $attributeFamily->id)
                ->delete();

            foreach ($structures as $structureData) {
                $structure = VariantStructure::create([
                    'attribute_family_id' => $attributeFamily->id,
                    'code'                => $structureData['code'],
                    'name'                => $structureData['name'] ?? $structureData['code'],
                    'levels'              => (int) $structureData['levels'],
                ]);

                foreach ($this->normalizeVariantAxes($structureData['axes'] ?? []) as $level => $attributeCodes) {
                    foreach ($attributeCodes as $position => $attributeCode) {
                        if ((int) $structureData['levels'] === 1 && $level === 'level_2') {
                            continue;
                        }

                        VariantStructureAxis::create([
                            'variant_structure_id' => $structure->id,
                            'attribute_id'         => $familyAttributes[$attributeCode]->id,
                            'level'                => $level,
                            'position'             => $position,
                        ]);
                    }
                }

                $storedAttributes = [];

                foreach (($structureData['placements'] ?? []) as $level => $codes) {
                    foreach ($codes as $attributeCode) {
                        if (isset($storedAttributes[$attributeCode])) {
                            continue;
                        }

                        $storedAttributes[$attributeCode] = true;

                        VariantStructureAttribute::create([
                            'variant_structure_id' => $structure->id,
                            'attribute_id'         => $familyAttributes[$attributeCode]->id,
                            'level'                => $level,
                        ]);
                    }
                }
            }
        });

        $currentStructures = VariantStructure::query()
            ->with(['axes.attribute', 'placements.attribute'])
            ->where('attribute_family_id', $attributeFamily->id)
            ->get();

        $currentSnapshots = $currentStructures
            ->mapWithKeys(fn (VariantStructure $structure): array => [
                $structure->code => $this->variantStructureSnapshot($structure),
            ])
            ->all();

        if ($previousSnapshots !== $currentSnapshots) {
            Event::dispatch('core.model.proxy.sync.variantStructure', [
                'old_values' => $previousSnapshots,
                'new_values' => $currentSnapshots,
                'model'      => $currentStructures->first() ?? $previousStructures->first(),
            ]);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.families.edit.variant-saved'),
            'data'    => $this->variantStructuresPayload($attributeFamily),
        ]);
    }

    /**
     * Save one variant structure.
     */
    protected function saveVariantStructure(AttributeFamily $attributeFamily, array $structureData): JsonResponse
    {
        validator(['structure' => $structureData], [
            'structure.id'                 => ['nullable', 'integer'],
            'structure.code'               => ['required', new Code],
            'structure.name'               => ['nullable', 'string'],
            'structure.levels'             => ['required', 'integer', 'in:1,2'],
            'structure.axes.level_1'       => ['required', 'array'],
            'structure.axes.level_1.*'     => ['required', 'string'],
            'structure.axes.level_2'       => ['array'],
            'structure.axes.level_2.*'     => ['string'],
            'structure.placements'         => ['array'],
            'structure.placements.*'       => ['array'],
            'structure.placements.*.*'     => ['string'],
        ])->validate();

        $familyAttributes = $attributeFamily->customAttributes()->get()->keyBy('code');
        $configurableAttributes = $attributeFamily->getConfigurableAttributes()->keyBy('code');
        $structureData['placements'] = $this->forceUniqueAttributesToVariant($structureData['placements'] ?? [], $familyAttributes);
        $levels = (int) $structureData['levels'];
        $axes = $this->normalizeVariantAxes($structureData['axes'] ?? []);
        $activeAxes = $levels === 2
            ? [...$axes['level_1'], ...$axes['level_2']]
            : $axes['level_1'];

        if (! count($axes['level_1']) || ($levels === 2 && ! count($axes['level_2']))) {
            abort(422, trans('validation.required', ['attribute' => 'structure.axes']));
        }

        if (count(array_unique($activeAxes)) !== count($activeAxes)) {
            abort(422, trans('validation.distinct', ['attribute' => 'structure.axes']));
        }

        $codeExists = VariantStructure::query()
            ->where('attribute_family_id', $attributeFamily->id)
            ->where('code', $structureData['code'])
            ->when(! empty($structureData['id']), fn ($query) => $query->where('id', '!=', $structureData['id']))
            ->exists();

        if ($codeExists) {
            abort(422, trans('validation.unique', ['attribute' => 'code']));
        }

        foreach ($activeAxes as $axisCode) {
            if (! $configurableAttributes->has($axisCode)) {
                abort(422, trans('validation.exists', ['attribute' => $axisCode]));
            }
        }

        foreach (($structureData['placements'] ?? []) as $level => $codes) {
            if (! in_array($level, ['common', 'sub_parent', 'variant'], true)) {
                abort(422, trans('validation.in', ['attribute' => $level]));
            }

            if ($levels === 1 && $level === 'sub_parent' && count($codes)) {
                abort(422, trans('validation.in', ['attribute' => $level]));
            }

            foreach ($codes as $attributeCode) {
                if (! $familyAttributes->has($attributeCode) || in_array($attributeCode, $activeAxes, true)) {
                    abort(422, trans('validation.exists', ['attribute' => $attributeCode]));
                }
            }
        }

        if (! empty($structureData['id'])) {
            $existing = VariantStructure::query()
                ->with('axes.attribute')
                ->where('attribute_family_id', $attributeFamily->id)
                ->find($structureData['id']);

            if ($existing) {
                $currentAxes = $existing->axes->map(fn ($axis) => $axis->attribute?->code)->filter()->sort()->values()->all();
                $newAxes = collect($activeAxes)->sort()->values()->all();

                $structureChanged = $currentAxes !== $newAxes || (int) $existing->levels !== $levels;

                if ($structureChanged && Product::query()->where('variant_structure_id', $existing->id)->whereHas('variants')->exists()) {
                    abort(422, trans('admin::app.catalog.families.edit.variant-structure-locked'));
                }
            }
        }

        $previousSnapshot = $this->variantStructureSnapshot(
            empty($structureData['id'])
                ? null
                : VariantStructure::query()
                    ->with(['axes.attribute', 'placements.attribute'])
                    ->where('attribute_family_id', $attributeFamily->id)
                    ->find($structureData['id'])
        );

        $structure = DB::transaction(function () use ($attributeFamily, $structureData, $familyAttributes, $levels) {
            $structure = ! empty($structureData['id'])
                ? VariantStructure::query()
                    ->where('attribute_family_id', $attributeFamily->id)
                    ->findOrFail($structureData['id'])
                : new VariantStructure(['attribute_family_id' => $attributeFamily->id]);

            $structure->fill([
                'code'   => $structureData['code'],
                'name'   => $structureData['name'] ?? $structureData['code'],
                'levels' => $levels,
            ]);

            $structure->save();

            VariantStructureAxis::query()->where('variant_structure_id', $structure->id)->delete();
            VariantStructureAttribute::query()->where('variant_structure_id', $structure->id)->delete();

            foreach ($this->normalizeVariantAxes($structureData['axes'] ?? []) as $level => $attributeCodes) {
                foreach ($attributeCodes as $position => $attributeCode) {
                    if ($levels === 1 && $level === 'level_2') {
                        continue;
                    }

                    VariantStructureAxis::create([
                        'variant_structure_id' => $structure->id,
                        'attribute_id'         => $familyAttributes[$attributeCode]->id,
                        'level'                => $level,
                        'position'             => $position,
                    ]);
                }
            }

            $storedAttributes = [];

            foreach (($structureData['placements'] ?? []) as $level => $codes) {
                foreach ($codes as $attributeCode) {
                    if (isset($storedAttributes[$attributeCode])) {
                        continue;
                    }

                    $storedAttributes[$attributeCode] = true;

                    VariantStructureAttribute::create([
                        'variant_structure_id' => $structure->id,
                        'attribute_id'         => $familyAttributes[$attributeCode]->id,
                        'level'                => $level,
                    ]);
                }
            }

            return $structure;
        });

        Event::dispatch('core.model.proxy.sync.variantStructure', [
            'old_values' => $previousSnapshot,
            'new_values' => $this->variantStructureSnapshot(
                VariantStructure::query()->with(['axes.attribute', 'placements.attribute'])->find($structure->id)
            ),
            'model'      => $structure,
        ]);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.families.edit.variant-saved'),
            'data'    => $this->variantStructuresPayload($attributeFamily, $structure->id)[0] ?? null,
        ]);
    }

    /**
     * Delete one variant structure.
     */
    public function deleteVariantStructure(int $id, int $structureId): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        $structure = VariantStructure::query()
            ->with(['axes.attribute', 'placements.attribute'])
            ->where('attribute_family_id', $attributeFamily->id)
            ->find($structureId);

        if ($structure instanceof VariantStructure) {
            $snapshot = $this->variantStructureSnapshot($structure);

            $structure->delete();

            Event::dispatch('core.model.proxy.sync.variantStructure', [
                'old_values' => $snapshot,
                'new_values' => [],
                'model'      => $structure,
            ]);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.families.delete-success'),
        ]);
    }

    /**
     * Readable snapshot of a variant structure, used for the family history diff.
     */
    protected function variantStructureSnapshot(?VariantStructure $structure): array
    {
        if (! $structure instanceof VariantStructure) {
            return [];
        }

        $snapshot = [
            'code'   => $structure->code,
            'name'   => $structure->name,
            'levels' => (string) $structure->levels,
        ];

        $grouped = [];

        foreach ($structure->axes as $axis) {
            if ($axis->attribute?->code) {
                $grouped['axes_'.$axis->level][] = $axis->attribute->code;
            }
        }

        foreach ($structure->placements as $placement) {
            if ($placement->attribute?->code) {
                $grouped['attributes_'.$placement->level][] = $placement->attribute->code;
            }
        }

        foreach ($grouped as $key => $codes) {
            sort($codes);

            $snapshot[$key] = implode(', ', $codes);
        }

        return $snapshot;
    }

    /**
     * Force unique attributes onto the variant level.
     */
    protected function forceUniqueAttributesToVariant(array $placements, $familyAttributes): array
    {
        $uniqueCodes = $familyAttributes
            ->filter(fn ($attribute): bool => (bool) $attribute->is_unique)
            ->keys()
            ->all();

        if ($uniqueCodes === []) {
            return $placements;
        }

        $relocated = [];

        foreach (['common', 'sub_parent'] as $level) {
            if (empty($placements[$level])) {
                continue;
            }

            $found = array_values(array_intersect($placements[$level], $uniqueCodes));

            if ($found === []) {
                continue;
            }

            $placements[$level] = array_values(array_diff($placements[$level], $uniqueCodes));

            $relocated = [...$relocated, ...$found];
        }

        if ($relocated !== []) {
            $placements['variant'] = array_values(array_unique([...($placements['variant'] ?? []), ...$relocated]));
        }

        return $placements;
    }

    /**
     * Normalize variant axes payload.
     */
    protected function normalizeVariantAxes(array $axes): array
    {
        if (array_is_list($axes)) {
            return [
                'level_1' => array_values(array_unique(array_filter([$axes[0] ?? null]))),
                'level_2' => array_values(array_unique(array_filter([$axes[1] ?? null]))),
            ];
        }

        return [
            'level_1' => array_values(array_unique(array_filter($axes['level_1'] ?? []))),
            'level_2' => array_values(array_unique(array_filter($axes['level_2'] ?? []))),
        ];
    }

    /**
     * Get configurable axis options for variant structure forms.
     */
    protected function variantAxisOptions(AttributeFamily $attributeFamily): array
    {
        return $attributeFamily->getConfigurableAttributes()
            ->map(fn ($attribute) => [
                'code'  => $attribute->code,
                'label' => $attribute->admin_name ?? $attribute->name ?? $attribute->code,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Reject a save that drops an attribute one of the family's variant structures
     * still uses as an axis, which would leave the structure selectable on the
     * configurable product form with an axis the family no longer has.
     *
     * The family membership check stays an anti-join: a large family holds more
     * attributes than a driver accepts bound parameters.
     */
    protected function guardVariantStructureAxes(AttributeFamily $attributeFamily): void
    {
        $missing = VariantStructureAxis::query()
            ->with('attribute')
            ->whereIn(
                'variant_structure_id',
                VariantStructure::query()->where('attribute_family_id', $attributeFamily->id)->select('id')
            )
            ->whereNotExists(fn (QueryBuilder $query) => $query
                ->from('attribute_group_mappings')
                ->join(
                    'attribute_family_group_mappings',
                    'attribute_group_mappings.attribute_family_group_id',
                    '=',
                    'attribute_family_group_mappings.id'
                )
                ->whereColumn('attribute_group_mappings.attribute_id', 'variant_structure_axes.attribute_id')
                ->where('attribute_family_group_mappings.attribute_family_id', $attributeFamily->id)
            )
            ->get()
            ->map(fn (VariantStructureAxis $axis): ?string => $axis->attribute?->code)
            ->filter()
            ->unique();

        if ($missing->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'code' => trans('admin::app.catalog.families.variant-axis-attribute-required', [
                'attributes' => $missing->implode(', '),
            ]),
        ]);
    }

    /**
     * Get grouped family attributes for variant structure forms.
     */
    protected function variantAttributeGroups(AttributeFamily $attributeFamily): array
    {
        return $attributeFamily->attributeFamilyGroupMappings()
            ->with('attributeGroups')
            ->get()
            ->map(function ($familyGroupMapping) {
                $attributeGroup = $familyGroupMapping->attributeGroups->first();

                return [
                    'code'       => $attributeGroup?->code ?? $attributeGroup?->id,
                    'label'      => ! empty($attributeGroup?->name) ? $attributeGroup->name : '['.$attributeGroup?->code.']',
                    'attributes' => $attributeGroup?->customAttributes($familyGroupMapping->attribute_family_id)
                        ->map(fn ($attribute) => [
                            'code'      => $attribute->code,
                            'label'     => ! empty($attribute->name) ? $attribute->name : '['.$attribute->code.']',
                            'type'      => $attribute->type ?? '',
                            'is_unique' => (bool) $attribute->is_unique,
                        ])
                        ->values()
                        ->toArray() ?? [],
                ];
            })
            ->values()
            ->toArray();
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
     * Check product has variant products and return family if exists.
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
