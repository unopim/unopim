<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CategoryBrowseRequest;
use Webkul\Admin\Http\Requests\CategoryChildrenForm;
use Webkul\Admin\Http\Requests\CategoryRequest;
use Webkul\Admin\Http\Requests\CategorySearchForm;
use Webkul\Admin\Http\Requests\CategoryTreeForm;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Resources\Catalog\CategoryTreeResource;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryRequestValidator;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Traits\HtmlPurifier;

class CategoryController extends Controller
{
    use HtmlPurifier;

    const DEFAULT_PAGE = 1;

    const SEARCH_PER_PAGE = 50;

    const VIEW_MODE_SESSION_KEY = 'catalog.categories.view_mode';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryRequestValidator $categoryValidator
    ) {}

    /**
     * Sanitize wysiwyg category-field values in additional_data before persisting.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizeAdditionalData(array $data): array
    {
        if (empty($data['additional_data'])) {
            return $data;
        }

        $fields = $this->categoryFieldRepository->findByField('status', true)
            ->where('enable_wysiwyg', '==', 1)
            ->where('type', '==', 'textarea');

        foreach ($fields as $field) {
            if ($field->value_per_locale) {
                foreach ($data['additional_data']['locale_specific'] ?? [] as $locale => $values) {
                    foreach ($values ?? [] as $code => $value) {
                        if (empty($value) || $field->code !== $code) {
                            continue;
                        }

                        $data['additional_data']['locale_specific'][$locale][$code] = $this->purifyText($value);
                    }
                }
            } else {
                foreach ($data['additional_data']['common'] ?? [] as $code => $value) {
                    if (empty($value) || $field->code !== $code) {
                        continue;
                    }

                    $data['additional_data']['common'][$code] = $this->purifyText($value);
                }
            }
        }

        return $data;
    }

    /**
     * Tree workspace, or the flat listing when `view=list` is asked for.
     */
    public function index(CategoryBrowseRequest $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return app(CategoryDataGrid::class)->toJson();
        }

        $viewMode = $this->resolveViewMode($request);

        $data = [
            'viewMode'            => $viewMode,
            'treeItems'           => [],
            'branchToParent'      => [],
            'selectedId'          => null,
            'panelMode'           => null,
            'category'            => null,
            'parentCategory'      => null,
            'breadcrumb'          => '',
            'leftCategoryFields'  => $this->categoryFieldRepository->getActiveCategoryFieldsBySection('left'),
            'rightCategoryFields' => $this->categoryFieldRepository->getActiveCategoryFieldsBySection('right'),
        ];

        if ($viewMode === 'list') {
            return view('admin::catalog.categories.index', $data);
        }

        $data['treeItems'] = CategoryTreeResource::collection($this->categoryRepository->getRootCategories())->toArray($request);

        if ($categoryId = $request->selectedCategoryId()) {
            $data['category'] = $this->categoryRepository->find($categoryId);
            $data['panelMode'] = $data['category'] ? 'edit' : null;
            $data['selectedId'] = $data['category']?->id;
        } elseif ($request->wantsCreatePanel()) {
            $data['panelMode'] = 'create';

            if ($parentId = $request->parentCategoryId()) {
                $data['parentCategory'] = $this->categoryRepository->find($parentId);
                $data['selectedId'] = $data['parentCategory']?->id;
            }
        }

        $revealed = $data['category'] ?? $data['parentCategory'];

        if ($revealed) {
            $data['branchToParent'] = CategoryTreeResource::collection(
                $this->categoryRepository->getPathNodes([$revealed->code])->toTree()
            )->toArray($request);

            $breadcrumbId = $data['panelMode'] === 'create' ? $revealed->id : $revealed->parent_id;

            $data['breadcrumb'] = $breadcrumbId
                ? ($this->categoryRepository->getBreadcrumbsForIds([$breadcrumbId])[$breadcrumbId] ?? '')
                : '';
        }

        return view('admin::catalog.categories.index', $data);
    }

    /**
     * The chosen view sticks for the rest of the session, so returning to the
     * listing lands where it was left. A deep link to a category outranks it —
     * the properties panel only exists in the tree.
     */
    private function resolveViewMode(CategoryBrowseRequest $request): string
    {
        if ($requested = $request->requestedView()) {
            session()->put(self::VIEW_MODE_SESSION_KEY, $requested);

            return $requested;
        }

        if ($request->selectedCategoryId() || $request->wantsCreatePanel()) {
            return 'tree';
        }

        $stored = session(self::VIEW_MODE_SESSION_KEY);

        return in_array($stored, ['tree', 'list'], true) ? $stored : 'tree';
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = $this->categoryRepository->getRootCategories();

        $categories = $this->transformCategoryTree($categories);

        $leftCategoryFields = $this->categoryFieldRepository->getActiveCategoryFieldsBySection('left');

        $rightCategoryFields = $this->categoryFieldRepository->getActiveCategoryFieldsBySection('right');

        return view('admin::catalog.categories.create', compact('categories', 'leftCategoryFields', 'rightCategoryFields'));
    }

    /**
     * Maps each category in the collection to a new value using the provided callback.
     *
     * @param  Collection  $categories  Collection of category objects.
     */
    public function transformCategoryTree(Collection $categories): array
    {
        return $categories->map(function ($category) {
            return [
                'id'       => $category->id,
                'code'     => $category->code,
                'name'     => $category->name,
                'children' => [],
                '_rgt'     => $category->_rgt,
                '_lft'     => $category->_lft,
            ];
        })->toArray();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $categoryRequest): RedirectResponse
    {
        Event::dispatch('catalog.category.create.before');

        try {
            $this->categoryValidator->validate($categoryRequest->only(['code', 'parent_id', 'additional_data']));
        } catch (ValidationException $e) {
            session()->flash('error', trans('admin::app.catalog.categories.create-failure'));

            throw $e;
        }

        $category = $this->categoryRepository->create($this->sanitizeAdditionalData($categoryRequest->only([
            'code',
            'locale',
            'name',
            'parent_id',
            'additional_data',
        ])));

        Event::dispatch('catalog.category.create.after', $category);

        session()->flash('success', trans('admin::app.catalog.categories.create-success'));

        return redirect()->route('admin.catalog.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $category = $this->categoryRepository->findOrFail($id);

        $categories = $this->categoryRepository->getRootCategories();

        $categories = $this->transformCategoryTree($categories);

        $branchToParent = CategoryTreeResource::collection(
            $this->categoryRepository->getTreeBranchToParent($category) ?? collect()
        )->toArray(request());

        $leftCategoryFields = $this->categoryFieldRepository->getActiveCategoryFieldsBySection('left');

        $rightCategoryFields = $this->categoryFieldRepository->getActiveCategoryFieldsBySection('right');

        return view('admin::catalog.categories.edit', compact('category', 'branchToParent', 'categories', 'leftCategoryFields', 'rightCategoryFields'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $categoryRequest, int $id): RedirectResponse
    {
        Event::dispatch('catalog.category.update.before', $id);

        if (! empty($categoryRequest->input('parent_id')) && $this->isRelatedToChannel($id)) {
            session()->flash('error', trans('admin::app.catalog.categories.can-not-update'));

            return redirect()->route('admin.catalog.categories.edit', ['id' => $id]);
        }

        if (! empty($categoryRequest->input('parent_id'))) {
            $parentId = (int) $categoryRequest->input('parent_id');
            $category = $this->categoryRepository->find($id);
            $parentCategory = $this->categoryRepository->find($parentId);

            if ($parentId === $id || ($category && $parentCategory && $parentCategory->isDescendantOf($category))) {
                session()->flash('error', trans('admin::app.catalog.categories.invalid-parent'));

                return redirect()->route('admin.catalog.categories.edit', ['id' => $id]);
            }
        }

        try {
            $this->categoryValidator->validate($categoryRequest->only(['code', 'parent_id', 'additional_data']), $id);
        } catch (ValidationException $e) {
            session()->flash('error', trans('admin::app.catalog.categories.update-failure'));

            throw $e;
        }

        $category = $this->categoryRepository->update($this->sanitizeAdditionalData($categoryRequest->only([
            'locale',
            'parent_id',
            core()->getRequestedLocaleCode(),
            'additional_data',
        ])), $id);

        Event::dispatch('catalog.category.update.after', $category);

        session()->flash('success', trans('admin::app.catalog.categories.update-success'));

        return redirect()->route('admin.catalog.categories.edit', ['id' => $id, 'locale' => core()->getRequestedLocaleCode()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryRepository->findOrFail($id);

        if ($this->isRelatedToChannel($category->id)) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-category-root'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('catalog.category.delete.before', $id);

            $category->delete($id);

            Event::dispatch('catalog.category.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-success', [
                    'name' => trans('admin::app.catalog.categories.category'),
                ]),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resources from database.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $suppressFlash = true;

        $categoryIds = $massDestroyRequest->input('indices');

        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);

            if (! isset($category)) {
                continue;
            }

            if ($this->isRelatedToChannel($category->id)) {
                $suppressFlash = false;

                return new JsonResponse(['message' => trans('admin::app.catalog.categories.delete-category-root')], JsonResponse::HTTP_BAD_REQUEST);
            }

            try {
                $suppressFlash = true;

                Event::dispatch('catalog.category.delete.before', $categoryId);

                $this->categoryRepository->delete($categoryId);

                Event::dispatch('catalog.category.delete.after', $categoryId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => trans('admin::app.catalog.categories.delete-failed'),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if (
            count($categoryIds) != 1
            || $suppressFlash == true
        ) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-success'),
            ]);
        }

        return redirect()->route('admin.catalog.categories.index');
    }

    /**
     * Check whether the current category is related to a channel or not.
     * If the category is assigned as root to any channel it can not have parent category.
     *
     * This method will fetch all root category ids from the channel. If `id` is present,
     * then it is not deletable and can not have a parent category.
     */
    private function isRelatedToChannel(int $categoryId): bool
    {
        return (bool) $this->channelRepository->pluck('root_category_id')->contains($categoryId);
    }

    /**
     * Roots of every category tree, plus the branches that have to be revealed
     * so the already selected categories are visible without expanding.
     *
     * Only the path down to each selection is sent — siblings at every level
     * are left to the lazy children endpoint, which keeps the payload
     * proportional to the number of selections rather than to the width of the
     * tree they sit in.
     */
    public function tree(CategoryTreeForm $request): JsonResponse
    {
        $pathNodes = $this->categoryRepository->getPathNodes($request->selectedCodes());

        return new JsonResponse([
            'data'          => CategoryTreeResource::collection($this->categoryRepository->getRootCategories())->toArray($request),
            'selected_tree' => CategoryTreeResource::collection($pathNodes->toTree())->toArray($request),
        ]);
    }

    /**
     * Fetch child categories for a given category ID.
     */
    public function children(CategoryChildrenForm $request): JsonResponse
    {
        $parentId = (int) $request->validated('id');

        $categoryId = (int) ($request->validated('category') ?? 0);

        if ($request->filled('page')) {
            $children = $this->categoryRepository->getChildCategoriesPaginated(
                $parentId,
                $categoryId,
                (int) $request->validated('page'),
                (int) ($request->validated('limit') ?? CategoryRepository::DEFAULT_PER_PAGE),
            );

            return new JsonResponse([
                ...$children,
                'data' => CategoryTreeResource::collection($children['data'])->toArray($request),
            ]);
        }

        $childCategories = $this->categoryRepository->getChildCategories($parentId, $categoryId);

        return new JsonResponse(CategoryTreeResource::collection($childCategories)->toArray($request));
    }

    /**
     * Paginated flat search across every tree, each hit carrying its breadcrumb
     * so identically named categories in different trees stay distinguishable.
     */
    public function search(CategorySearchForm $request): JsonResponse
    {
        $locale = $request->validated('locale') ?? core()->getRequestedLocaleCode();

        $searchQuery = trim((string) $request->validated('query', ''));

        $query = $this->categoryRepository->getModel()->newQuery();

        if ($codes = $request->codes()) {
            $query->whereIn('code', $codes);
        }

        if ($searchQuery !== '') {
            $query->where(function ($builder) use ($searchQuery, $locale) {
                $builder->where('additional_data->locale_specific->'.$locale.'->name', 'LIKE', '%'.$searchQuery.'%')
                    ->orWhere('code', 'LIKE', '%'.$searchQuery.'%');
            });
        }

        $page = max(self::DEFAULT_PAGE, (int) ($request->validated('page') ?? self::DEFAULT_PAGE));

        $paginator = $query->defaultOrder()->paginate(self::SEARCH_PER_PAGE, ['*'], 'page', $page);

        $breadcrumbs = $this->categoryRepository->getBreadcrumbsForIds(
            $paginator->getCollection()->pluck('id')->all(),
            $locale
        );

        $results = $paginator->getCollection()->map(fn ($category) => [
            'id'    => $category->id,
            'code'  => $category->code,
            'label' => $category->additional_data['locale_specific'][$locale]['name'] ?? '['.$category->code.']',
            'path'  => $breadcrumbs[(int) $category->id] ?? null,
        ])->values();

        return new JsonResponse([
            'data'     => $results,
            'page'     => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ]);
    }
}
