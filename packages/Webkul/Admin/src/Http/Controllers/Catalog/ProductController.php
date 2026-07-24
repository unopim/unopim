<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Filters\ProductPropertyFilters;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Admin\Http\Requests\VariantChildrenForm;
use Webkul\Admin\Http\Requests\VariantNodeForm;
use Webkul\Admin\Traits\AttributeColumnTrait;
use Webkul\Attribute\Models\AttributeOptionProxy;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Sku;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\VariantStructureRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

class ProductController extends Controller
{
    use AttributeColumnTrait;

    /*
    * Using const variable for status
    */
    const ACTIVE_STATUS = 1;

    /**
     * Default page size for the paginated {@see variantChildren()} listing.
     */
    const VARIANT_CHILDREN_PER_PAGE = 50;

    /**
     * Upper bound for a client-requested {@see variantChildren()} page size.
     */
    const VARIANT_CHILDREN_MAX_PER_PAGE = 200;

    /**
     * Upper bound for the axis options a {@see variantChildren()} search term
     * may resolve to before it is used to filter children.
     */
    const VARIANT_CHILDREN_MAX_MATCHED_OPTIONS = 500;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductValuesValidator $valuesValidator,
        protected ChannelRepository $channelRepository,
        protected AttributeRepository $attributeRepository,
        protected VariantStructureRepository $variantStructureRepository,
        protected VariantStructurePlanner $variantStructurePlanner,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse|BinaryFileResponse
    {
        if (request()->ajax()) {
            return app(ProductDataGrid::class)->toJson();
        }

        return view('admin::catalog.products.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'type'                => 'required',
            'attribute_family_id' => 'required',
            'sku'                 => ['required', 'unique:products,sku', new Sku],
        ]);

        $data = request()->only([
            'type',
            'attribute_family_id',
            'sku',
            'family',
            'variant_structure_id',
        ]);

        $data['variant_structure_id'] = request()->input('variant_structure_id');

        if (ProductType::hasVariants($data['type'])) {
            $structures = $this->variantStructureRepository->findWhere([
                'attribute_family_id' => $data['attribute_family_id'],
            ]);

            if (! empty($data['variant_structure_id'])) {
                $structure = $structures->firstWhere('id', (int) $data['variant_structure_id']);

                if (! $structure) {
                    return new JsonResponse([
                        'errors' => [
                            'variant_structure_id' => [trans('admin::app.catalog.products.index.create.invalid-variant-structure')],
                        ],
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }

                $data['variant_structure_id'] = $structure->id;
                $data['super_attributes'] = $this->variantStructurePlanner->allAxisCodes($structure);
            } else {
                if ($structures->isEmpty()) {
                    return new JsonResponse([
                        'errors' => [
                            'attribute_family_id' => [trans('admin::app.catalog.products.index.create.no-variant-structure')],
                        ],
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }

                return new JsonResponse([
                    'data' => [
                        'variant_structures' => $structures->map(fn ($s) => [
                            'id'     => $s->id,
                            'name'   => $s->name ?: $s->code,
                            'code'   => $s->code,
                            'levels' => $s->levels,
                        ])->values(),
                    ],
                ]);
            }
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
     * Create a new `variant_group` or `simple` node under a configurable (or
     * one of its `variant_group` children), for the Variant Inheritance
     * Editor's "pick a new axis value -> create -> navigate" sidebar flow.
     * The new node owns only its fixed axis value; every other attribute is
     * resolved from the ancestor chain at read time (see VariantValueResolver).
     * The duplicate check and sku generation both read the parent's existing
     * children, so they run under the parent's row lock alongside the insert.
     */
    public function createVariantNode(VariantNodeForm $request, int $configurableId): JsonResponse
    {
        $configurable = $this->productRepository->findOrFail($configurableId);

        if ($configurable->type !== 'configurable' || ! $configurable->variantStructure) {
            abort(404);
        }

        $role = $request->input('role');

        $parent = $this->resolveVariantNodeParent($configurable, $request->input('parent_id'), $role);

        if (! $parent) {
            abort(404);
        }

        $axisValues = $request->input('values');

        $node = DB::transaction(function () use ($configurable, $parent, $role, $axisValues, $request) {
            ProductProxy::modelClass()::whereKey($parent->id)->lockForUpdate()->first();

            if ($this->variantNodeExists($parent, $role, $axisValues)) {
                return null;
            }

            $typeInstance = $configurable->getTypeInstance();

            if ($role === 'variant_group') {
                return $typeInstance->createVariantGroup($configurable, [
                    'group_values' => $axisValues,
                    'sku'          => $request->input('sku') ?: $this->uniqueVariantNodeSku($configurable, $configurable, $axisValues),
                ]);
            }

            return $typeInstance->createVariant($configurable, $configurable->super_attributes, [
                'parent_id'                      => $parent->id,
                'sku'                            => $request->input('sku') ?: $this->uniqueVariantNodeSku($configurable, $parent, $axisValues),
                AbstractType::PRODUCT_VALUES_KEY => [
                    AbstractType::COMMON_VALUES_KEY => $axisValues,
                ],
            ]);
        });

        if (! $node) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.edit.types.configurable.create.variant-already-exists'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'data' => [
                'id'           => $node->id,
                'redirect_url' => route('admin.catalog.products.edit', $node->id),
            ],
        ]);
    }

    /**
     * Resolve the parent a new variant node should be created under, given
     * the requested `parent_id` (null means directly under the configurable)
     * and the node's `role`. Returns null for any combination outside the
     * configurable's own subtree/structure shape:
     * - a `variant_group` can only be created directly under the configurable,
     *   and only when the structure has 2 levels.
     * - a `simple` node goes directly under the configurable in a 1-level
     *   structure, or under one of the configurable's own `variant_group`
     *   children in a 2-level structure.
     */
    protected function resolveVariantNodeParent(Product $configurable, mixed $parentId, string $role): ?Product
    {
        $levels = (int) $configurable->variantStructure->levels;

        if (empty($parentId) || (int) $parentId === $configurable->id) {
            if ($role === 'variant_group') {
                return $levels === 2 ? $configurable : null;
            }

            return $levels === 1 ? $configurable : null;
        }

        if ($role !== 'simple') {
            return null;
        }

        $parent = $this->productRepository->find($parentId);

        if (
            ! $parent
            || $parent->type !== 'variant_group'
            || (int) $parent->parent_id !== $configurable->id
        ) {
            return null;
        }

        return $parent;
    }

    /**
     * A sibling already fixed on the same axis combination makes the new node
     * a duplicate: two children of one parent cannot resolve to the same
     * variant. Matched in SQL on the JSON axis values rather than in PHP so a
     * parent with thousands of children stays cheap.
     */
    protected function variantNodeExists(Product $parent, string $role, array $axisValues): bool
    {
        $query = $parent->variants()->where('type', $role);

        foreach ($axisValues as $code => $value) {
            $query->where('values->common->'.$code, $value);
        }

        return $query->exists();
    }

    /**
     * Default sku for a newly created variant node: the resolved base sku
     * (the parent's own sku, or the configurable's when creating directly
     * under it) plus a slug of every new axis value, made unique with a
     * numeric suffix on collision.
     */
    protected function uniqueVariantNodeSku(Product $configurable, Product $base, array $axisValues): string
    {
        $prefix = $base->id === $configurable->id ? $configurable->sku : $base->sku;

        $slug = Str::slug(implode('-', $axisValues));

        $candidate = $prefix.'-'.$slug;

        $suffix = 1;

        while ($this->productRepository->findOneByField('sku', $candidate)) {
            $suffix++;

            $candidate = $prefix.'-'.$slug.'-'.$suffix;
        }

        return $candidate;
    }

    /**
     * Paginated, searchable listing of a configurable's CREATED variant
     * children — the on-demand counterpart to the ancestry-only
     * {@see buildVariantTree()} payload, so the sidebar axis-nav can browse
     * thousands of variants without ever inlining them into the page.
     *
     * `parent_id` selects which node to list direct children of (null/absent
     * means the configurable itself). The expected child role is
     * `variant_group` when listing the configurable's own children in a
     * 2-level structure, `simple` otherwise (a `variant_group`'s children,
     * or any node in a 1-level structure). `axis` (validated against the
     * configurable's own axis codes by {@see VariantChildrenForm}) is the
     * attribute whose `values.common` option code labels each child; `query`
     * filters by that option's translated label or the child's sku.
     */
    public function variantChildren(VariantChildrenForm $request, int $configurableId): JsonResponse
    {
        $configurable = $this->productRepository->findOrFail($configurableId);

        if ($configurable->type !== 'configurable' || ! $configurable->variantStructure) {
            abort(404);
        }

        $parent = $this->resolveVariantChildrenParent($configurable, $request->input('parent_id'));

        if (! $parent) {
            abort(404);
        }

        $search = trim((string) $request->input('query', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max((int) ($request->input('perPage') ?: self::VARIANT_CHILDREN_PER_PAGE), 1), self::VARIANT_CHILDREN_MAX_PER_PAGE);

        $expectedRole = $parent->id === $configurable->id && (int) $configurable->variantStructure->levels === 2
            ? 'variant_group'
            : 'simple';

        $axes = $this->variantChildrenAxisCodes($configurable, $expectedRole);

        $childrenQuery = $parent->variants()->where('type', $expectedRole);

        if ($search !== '') {
            $matchingOptionCodes = AttributeOptionProxy::modelClass()::whereHas('attribute', fn ($query) => $query->whereIn('code', $axes))
                ->whereTranslationLike('label', '%'.$search.'%')
                ->limit(self::VARIANT_CHILDREN_MAX_MATCHED_OPTIONS)
                ->pluck('code');

            $childrenQuery->where(function ($builder) use ($axes, $search, $matchingOptionCodes) {
                $builder->where('sku', 'LIKE', '%'.$search.'%');

                if ($matchingOptionCodes->isNotEmpty()) {
                    foreach ($axes as $axisCode) {
                        $builder->orWhereIn('values->common->'.$axisCode, $matchingOptionCodes);
                    }
                }
            });
        }

        $paginator = $childrenQuery->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        $optionCodes = $paginator->getCollection()
            ->flatMap(fn (Product $child) => array_map(
                fn (string $axisCode) => $child->values['common'][$axisCode] ?? null,
                $axes
            ))
            ->filter()
            ->unique()
            ->values();

        $labelsByCode = AttributeOptionProxy::modelClass()::whereIn('code', $optionCodes)
            ->with('translations')
            ->get()
            ->keyBy('code');

        $childIds = $paginator->getCollection()->pluck('id')->all();

        $channel = core()->getRequestedChannel();
        $locale = core()->getRequestedLocale();

        $completenessByProduct = ($channel && $locale)
            ? DB::table('product_completeness')
                ->whereIn('product_id', $childIds)
                ->where('channel_id', $channel->id)
                ->where('locale_id', $locale->id)
                ->pluck('score', 'product_id')
            : collect();

        $isGroupList = $expectedRole === 'variant_group';

        $leafTotals = $isGroupList
            ? ProductProxy::modelClass()::whereIn('parent_id', $childIds)
                ->selectRaw('parent_id, count(*) as aggregate')
                ->groupBy('parent_id')
                ->pluck('aggregate', 'parent_id')
            : collect();

        $leafComplete = ($isGroupList && $channel && $locale)
            ? DB::table('products as p')
                ->join('product_completeness as pc', 'pc.product_id', '=', 'p.id')
                ->whereIn('p.parent_id', $childIds)
                ->where('pc.channel_id', $channel->id)
                ->where('pc.locale_id', $locale->id)
                ->where('pc.score', 100)
                ->select('p.parent_id')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('p.parent_id')
                ->pluck('aggregate', 'p.parent_id')
            : collect();

        $imageAttributes = $configurable->getImageAttributes();
        $channelCode = $channel?->code;
        $localeCode = $locale?->code;

        $options = $paginator->getCollection()->map(function (Product $child) use ($axes, $isGroupList, $labelsByCode, $completenessByProduct, $leafTotals, $leafComplete, $imageAttributes, $channelCode, $localeCode) {
            $axisValues = [];

            foreach ($axes as $axisCode) {
                if ($optionCode = $child->values['common'][$axisCode] ?? null) {
                    $axisValues[$axisCode] = $labelsByCode->get($optionCode)?->label ?: "[{$optionCode}]";
                }
            }

            $label = $axisValues ? implode(', ', $axisValues) : $child->sku;

            $imagePath = $child->getProductDisplayImage($channelCode, $localeCode, $imageAttributes);

            return [
                'id'              => $child->id,
                'axisValues'      => $axisValues,
                'label'           => $label,
                'sku'             => $child->sku,
                'image'           => $imagePath ? Storage::url($imagePath) : null,
                'completeness'    => $isGroupList ? null : (isset($completenessByProduct[$child->id]) ? (int) $completenessByProduct[$child->id] : null),
                'variantTotal'    => $isGroupList ? (int) ($leafTotals[$child->id] ?? 0) : null,
                'variantComplete' => $isGroupList ? (int) ($leafComplete[$child->id] ?? 0) : null,
                'redirect_url'    => route('admin.catalog.products.edit', $child->id),
            ];
        })->values();

        return new JsonResponse([
            'options'  => $options,
            'page'     => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'total'    => $paginator->total(),
        ]);
    }

    /**
     * The axis codes a listing's children are fixed on: level 1 for the
     * `variant_group` children of a 2-level configurable, the leaf level
     * (2 when the structure has one, 1 otherwise) for `simple` children.
     *
     * @return array<int, string>
     */
    protected function variantChildrenAxisCodes(Product $configurable, string $expectedRole): array
    {
        $structure = $configurable->variantStructure;

        $byLevel = $this->variantStructurePlanner->axisCodesByLevel($structure);

        $level = $expectedRole === 'variant_group' || (int) $structure->levels === 1
            ? 'level_1'
            : 'level_2';

        return $byLevel[$level] ?? [];
    }

    /**
     * Resolve the parent a variant-children listing is scoped to, given the
     * requested `parent_id` (null means direct children of the configurable
     * itself). Returns null for any parent outside the configurable's own
     * subtree — mirroring {@see resolveVariantNodeParent()}'s guard, but
     * without a `role`: the caller derives the expected child role from the
     * structure's level count once the parent is resolved.
     */
    protected function resolveVariantChildrenParent(Product $configurable, mixed $parentId): ?Product
    {
        if (empty($parentId) || (int) $parentId === $configurable->id) {
            return $configurable;
        }

        $parent = $this->productRepository->find($parentId);

        if (
            ! $parent
            || $parent->type !== 'variant_group'
            || (int) $parent->parent_id !== $configurable->id
        ) {
            return null;
        }

        return $parent;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $product = $this->productRepository->findOrFail($id);

        $requestedChannelId = core()->getRequestedChannel()->id;

        $requiredAttributes = $product->getCompletenessAttributes($requestedChannelId, core()->getRequestedLocale()->id)
            ->keyBy('attribute_id')
            ->map(fn ($item) => $item->attribute_id)
            ->toArray();

        $scores = $product->getCompletenessScore($requestedChannelId);

        $averageScore = count($scores) ? round(array_sum(array_column($scores, 'score')) / count($scores)) : null;

        $associations = $product->values['associations'] ?? [];

        $linkedProducts = [
            'up_sells'         => $this->normalizeLinkedProducts($associations['up_sells'] ?? []),
            'cross_sells'      => $this->normalizeLinkedProducts($associations['cross_sells'] ?? []),
            'related_products' => $this->normalizeLinkedProducts($associations['related_products'] ?? []),
        ];

        $variantTree = $this->buildVariantTree($product);

        $variantFieldLocks = $this->buildVariantFieldLocks($product);

        return view('admin::catalog.products.edit', compact('product', 'requiredAttributes', 'scores', 'averageScore', 'linkedProducts', 'variantTree', 'variantFieldLocks'));
    }

    /**
     * Resolve, for a variant node, which family attributes are inherited (locked)
     * rather than editable at this level, and the value each inherits from its
     * owning ancestor. Placement (`common`/`sub_parent`/`variant`) decides the
     * level an attribute is edited at; anything not owned at the current node's
     * level is locked and shows the ancestor's value. `sku` and the axis
     * attributes are never locked. Returns null for non-variant products.
     *
     * @return array{currentLevel: string, locks: array<string, array{level: string, value: mixed}>}|null
     */
    protected function buildVariantFieldLocks(Product $product): ?array
    {
        $configurable = $this->resolveConfigurableForVariantTree($product);

        if (! $configurable || ! ($structure = $configurable->variantStructure)) {
            return null;
        }

        $currentLevel = match ($product->type) {
            'configurable'  => 'common',
            'variant_group' => 'sub_parent',
            'simple'        => 'variant',
            default         => null,
        };

        if ($currentLevel === null) {
            return null;
        }

        $structure->loadMissing(['axes.attribute', 'placements.attribute']);

        $allAxisCodes = $this->variantStructurePlanner->allAxisCodes($structure);

        $groupAncestor = $product->type === 'simple' && $product->parent?->type === 'variant_group'
            ? $product->parent
            : null;

        $ownerByLevel = [
            'common'     => $configurable,
            'sub_parent' => $groupAncestor,
        ];

        $channelCode = core()->getRequestedChannelCode();
        $localeCode = core()->getRequestedLocaleCode();

        $levelOrder = ['common' => 0, 'sub_parent' => 1, 'variant' => 2];
        $currentOrder = $levelOrder[$currentLevel];

        $locks = [];
        $hidden = [];

        foreach ($configurable->attribute_family->customAttributes as $attribute) {
            if ($attribute->code === 'sku' || in_array($attribute->code, $allAxisCodes, true)) {
                continue;
            }

            $placement = $this->variantStructurePlanner->placementOf($structure, $attribute->code);

            $placementOrder = $levelOrder[$placement] ?? 0;

            if ($placementOrder === $currentOrder) {
                continue;
            }

            if ($placementOrder > $currentOrder) {
                $hidden[] = $attribute->code;

                continue;
            }

            $owner = $ownerByLevel[$placement] ?? null;

            $locks[$attribute->code] = [
                'level'   => $placement,
                'ownerId' => $owner?->id,
                'value'   => $owner ? $attribute->getValueFromProductValues($owner->values ?? [], $channelCode, $localeCode) : null,
            ];
        }

        return [
            'currentLevel' => $currentLevel,
            'locks'        => $locks,
            'hidden'       => $hidden,
        ];
    }

    /**
     * Build the blueprint consumed by the Variant Inheritance Editor: axes,
     * common-scope family attributes tagged with axis/placement metadata, and
     * the ANCESTRY chain of the node being edited only — the configurable,
     * plus (when editing a `variant_group` or `simple` leaf) every ancestor
     * between it and the configurable. Siblings and unrelated descendants are
     * never inlined, so the payload stays constant-size no matter how many
     * thousands of variants a configurable owns; the sidebar fetches
     * sibling/child nodes on demand via the paginated, searchable
     * {@see variantChildren()} endpoint. The frontend resolves inheritance
     * client-side, so nodes carry only what they own — never a resolved or
     * merged value. Returns null for legacy configurables (no
     * `variant_structure_id`), which keep the flat UI untouched.
     *
     * Any node in the subtree - the configurable itself, a `variant_group`,
     * or a `simple` leaf - can be passed in: the tree is always built from
     * the configurable ancestor (same attributes/axes payload) so the sidebar
     * axis-nav renders identically regardless of which node's edit page is
     * open. `currentNodeId` tells the frontend which node to highlight;
     * `configurableId` is the ancestor the tree was built from.
     *
     * @return array{levels: int, axesByLevel: array<string, array<int, string>>, attributes: array<int, array<string, mixed>>, nodes: array<string, array<string, mixed>>, axisLabels: array<string, string>, currentNodeId: int, configurableId: int}|null
     */
    protected function buildVariantTree(Product $product): ?array
    {
        $configurable = $this->resolveConfigurableForVariantTree($product);

        if (! $configurable || ! ($structure = $configurable->variantStructure)) {
            return null;
        }

        $structure->loadMissing(['axes.attribute', 'placements.attribute']);

        $allAxisCodes = $this->variantStructurePlanner->allAxisCodes($structure);

        $attributes = $configurable->attribute_family
            ->customAttributes()
            ->where('attributes.value_per_locale', 0)
            ->where('attributes.value_per_channel', 0)
            ->with(['translations'])
            ->get()
            ->unique('code')
            ->map(function ($attribute) use ($structure, $allAxisCodes) {
                $isAxis = in_array($attribute->code, $allAxisCodes, true);

                return [
                    'code'        => $attribute->code,
                    'attributeId' => $attribute->id,
                    'label'       => $attribute->name ?: $attribute->code,
                    'type'        => $attribute->type,
                    'options'     => null,
                    'isAxis'      => $isAxis,
                    'placement'   => $this->variantStructurePlanner->placementOf($structure, $attribute->code),
                ];
            })
            ->values();

        $nodes = $this->buildVariantNodeAncestry($configurable, $product, $allAxisCodes);

        return [
            'levels'         => (int) $structure->levels,
            'axesByLevel'    => $this->variantStructurePlanner->axisCodesByLevel($structure),
            'attributes'     => $attributes->all(),
            'nodes'          => $nodes,
            'axisLabels'     => $this->variantAxisLabels($nodes),
            'currentNodeId'  => $product->id,
            'configurableId' => $configurable->id,
            'totalVariants'  => $this->countVariantLeaves($configurable, (int) $structure->levels),
        ];
    }

    /**
     * Count the configurable's `simple` leaves — direct children in a 1-level
     * structure, grandchildren through its `variant_group`s in a 2-level one.
     * Inlined into the tree payload so the sidebar does not spend a request
     * fetching a single row just to read its total.
     */
    protected function countVariantLeaves(Product $configurable, int $levels): int
    {
        $leaves = ProductProxy::modelClass()::where('type', 'simple');

        if ($levels === 2) {
            return $leaves->whereIn(
                'parent_id',
                ProductProxy::modelClass()::where('parent_id', $configurable->id)
                    ->where('type', 'variant_group')
                    ->select('id')
            )->count();
        }

        return $leaves->where('parent_id', $configurable->id)->count();
    }

    /**
     * Resolve display labels for the axis option codes actually used across the
     * variant tree (e.g. `color_00014` → "Lavender 00014"), so the nav shows
     * labels instead of raw codes. Only the used codes are looked up — never
     * the attribute's full (potentially huge) option set.
     *
     * @param  array<string, array<string, mixed>>  $nodes
     * @return array<string, string>
     */
    protected function variantAxisLabels(array $nodes): array
    {
        $codes = [];

        foreach ($nodes as $node) {
            foreach (($node['axisFix'] ?? []) as $optionCode) {
                $codes[$optionCode] = true;
            }
        }

        if (empty($codes)) {
            return [];
        }

        $labels = [];

        foreach (AttributeOptionProxy::modelClass()::whereIn('code', array_keys($codes))->with('translations')->get() as $option) {
            $labels[$option->code] = $option->label ?: $option->code;
        }

        return $labels;
    }

    /**
     * Resolve the configurable ancestor a variant tree should be built from:
     * the product itself when it's already a configurable, or its
     * `configurable` ancestor when it's a `variant_group`/`simple` node.
     * Walk depth is guarded since it's bounded by the structure's level
     * count (at most 2 today).
     */
    protected function resolveConfigurableForVariantTree(Product $product): ?Product
    {
        if ($product->type === 'configurable') {
            return $product;
        }

        if (! in_array($product->type, ['variant_group', 'simple'], true)) {
            return null;
        }

        $ancestor = $product->parent;
        $guard = 0;

        while ($ancestor && $ancestor->type !== 'configurable' && $guard++ < 10) {
            $ancestor = $ancestor->parent;
        }

        return $ancestor?->type === 'configurable' ? $ancestor : null;
    }

    /**
     * Build the ancestry-only `nodes` map for the variant tree: the
     * configurable, plus — when `$current` is a `variant_group` or `simple`
     * leaf — every ancestor between it and the configurable (current leaf ->
     * its `variant_group` -> the configurable). Siblings and any other
     * descendant are never visited, so this runs in O(structure levels)
     * regardless of how many variants the configurable owns. Walk depth is
     * guarded since it's bounded by the structure's level count (at most 2
     * today).
     *
     * @param  array<int, string>  $allAxisCodes
     * @return array<string, array<string, mixed>>
     */
    protected function buildVariantNodeAncestry(Product $configurable, Product $current, array $allAxisCodes): array
    {
        $chain = [];

        $node = $current;
        $guard = 0;

        while ($node && $guard++ < 10) {
            $chain[] = $node;

            if ($node->id === $configurable->id) {
                break;
            }

            $node = $node->parent;
        }

        $chain = array_reverse($chain);

        $channel = core()->getRequestedChannel();
        $locale = core()->getRequestedLocale();
        $chainIds = array_map(fn (Product $n) => $n->id, $chain);

        $scoresByProduct = ($channel && $locale)
            ? DB::table('product_completeness')
                ->whereIn('product_id', $chainIds)
                ->where('channel_id', $channel->id)
                ->where('locale_id', $locale->id)
                ->pluck('score', 'product_id')
            : collect();

        $groupIds = array_values(array_filter(array_map(
            fn (Product $n) => $n->type === 'variant_group' ? $n->id : null,
            $chain
        )));

        $leafTotals = ! empty($groupIds)
            ? ProductProxy::modelClass()::whereIn('parent_id', $groupIds)
                ->selectRaw('parent_id, count(*) as aggregate')
                ->groupBy('parent_id')
                ->pluck('aggregate', 'parent_id')
            : collect();

        $leafComplete = (! empty($groupIds) && $channel && $locale)
            ? DB::table('products as p')
                ->join('product_completeness as pc', 'pc.product_id', '=', 'p.id')
                ->whereIn('p.parent_id', $groupIds)
                ->where('pc.channel_id', $channel->id)
                ->where('pc.locale_id', $locale->id)
                ->where('pc.score', 100)
                ->select('p.parent_id')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('p.parent_id')
                ->pluck('aggregate', 'p.parent_id')
            : collect();

        $imageAttributes = $configurable->getImageAttributes();
        $channelCode = $channel?->code;
        $localeCode = $locale?->code;

        $nodes = [];
        $parentId = null;

        foreach ($chain as $ancestor) {
            $owned = $ancestor->values['common'] ?? [];

            $imagePath = $ancestor->getProductDisplayImage($channelCode, $localeCode, $imageAttributes);

            $isGroup = $ancestor->type === 'variant_group';

            $nodes[(string) $ancestor->id] = [
                'id'              => $ancestor->id,
                'role'            => $ancestor->type,
                'parentId'        => $parentId,
                'axisFix'         => array_intersect_key($owned, array_flip($allAxisCodes)),
                'owned'           => $owned,
                'sku'             => $ancestor->sku,
                'image'           => $imagePath ? Storage::url($imagePath) : null,
                'completeness'    => $isGroup ? null : (isset($scoresByProduct[$ancestor->id]) ? (int) $scoresByProduct[$ancestor->id] : null),
                'variantTotal'    => $isGroup ? (int) ($leafTotals[$ancestor->id] ?? 0) : null,
                'variantComplete' => $isGroup ? (int) ($leafComplete[$ancestor->id] ?? 0) : null,
            ];

            $parentId = $ancestor->id;
        }

        return $nodes;
    }

    /**
     * Resolve linked product SKUs to normalized (image-ready) payloads for the edit view.
     */
    protected function normalizeLinkedProducts(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }

        $products = $this->productRepository->with(['attribute_family'])->findWhereIn('sku', $skus);

        /**
         * Image attributes are resolved once per attribute family (not once
         * per product row) to avoid an N+1 query for every linked product.
         */
        $imageAttributesByFamily = [];

        return $products
            ->map(function ($item) use (&$imageAttributesByFamily) {
                $familyId = $item->attribute_family_id;

                $imageAttributesByFamily[$familyId] ??= $item->attribute_family
                    ? $item->attribute_family->customAttributes()->where('type', 'image')->get()
                    : collect();

                return $item->normalizeWithImage(imageAttributes: $imageAttributesByFamily[$familyId]);
            })
            ->values()
            ->all();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductForm $request, int $id): RedirectResponse
    {
        Event::dispatch('catalog.product.update.before', $id);

        $configurableValues = [];

        $data = $request->all();

        $product = $this->productRepository->find($id);

        foreach (($product?->parent?->super_attributes ?? []) as $attr) {
            $attrCode = $attr->code;

            $configurableValues[$attrCode] = $data['values']['common'][$attrCode] ?? null;
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

        try {
            $product = $this->productRepository->update($data, $id);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstMessage = ! empty($errors) ? (array_values($errors)[0][0] ?? $e->getMessage()) : $e->getMessage();

            session()->flash('error', $firstMessage);

            return back()->withInput();
        }

        Event::dispatch('catalog.product.update.after', $product);

        ProductCompletenessJob::dispatch([$id]);

        session()->flash('success', trans('admin::app.catalog.products.update-success'));

        return redirect()->route('admin.catalog.products.edit', [
            'id'      => $id,
            'channel' => core()->getRequestedChannelCode(),
            'locale'  => core()->getRequestedLocaleCode(),
        ]);
    }

    /**
     * Copy a given Product.
     */
    public function copy(int $id): JsonResponse
    {
        try {
            $product = $this->productRepository->copy($id);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
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
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
        ], JsonResponse::HTTP_OK);
    }

    /**
     * To be manually invoked when data is seeded into products.
     */
    public function sync(): RedirectResponse
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     */
    public function search(): JsonResponse
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

    public function filterableAttributes(): JsonResponse
    {
        if (! bouncer()->hasPermission('catalog.products')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $query = $this->attributeRepository->getModel()->newQuery()
            ->where('is_filterable', true)
            ->with('translations');

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereTranslationLike('name', '%'.$search.'%')
                    ->orWhere('code', 'LIKE', '%'.$search.'%');
            });
        }

        $page = max(1, (int) request('page', 1));

        $paginator = $query->orderBy('id')->paginate(20, ['*'], 'page', $page);

        $options = $paginator->getCollection()->map(function ($attribute) {
            $column = $this->buildColumnDefinition($attribute);

            unset($column['closure']);

            $column['visible'] = false;

            return $column;
        })->values();

        if ($paginator->currentPage() === 1) {
            $options = collect(ProductPropertyFilters::pickerOptions($search))->concat($options)->values();
        }

        return new JsonResponse([
            'options'  => $options,
            'page'     => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ]);
    }

    /**
     * Check variant configurable attributes uniqueness
     */
    public function checkVariantUniqueness(): JsonResponse
    {
        $variantAttributes = request()->input('variantAttributes');

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
        $channel = $this->channelRepository->findOneByField('code', request()->channel);

        if (! $channel) {
            return new JsonResponse([
                'locales' => [],
            ]);
        }

        $locales = $channel->locales()->get();

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
        $attributes = $product->getEditableAttributes()->where('ai_translate', 1)->select('code', 'name', 'type', 'ai_translate');
        $attributeOptions = [];

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $attributeOptions[] = [
                    'id'    => $attribute['code'],
                    'label' => $attribute['name'],
                ];
            }
        }

        return new JsonResponse([
            'attributes' => $attributeOptions,
        ]);
    }
}
