<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CategoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryRequestValidator;
use Webkul\Core\Repositories\ChannelRepository;

class CategoryController extends Controller
{
    const DEFAULT_PAGE = 1;

    const SEARCH_PER_PAGE = 50;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository
    ) {
        $this->categoryValidator = new CategoryRequestValidator($this->categoryRepository, $this->categoryFieldRepository, $this->channelRepository);
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(CategoryDataGrid::class)->toJson();
        }

        return view('admin::catalog.categories.index');
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

        $category = $this->categoryRepository->create($categoryRequest->only([
            'code',
            'locale',
            'name',
            'parent_id',
            'additional_data',
        ]));

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

        $category = $this->categoryRepository->find($id);

        $branchToParent = $this->categoryRepository->getTreeBranchToParent($category);

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

        $category = $this->categoryRepository->update($categoryRequest->only([
            'locale',
            'parent_id',
            core()->getRequestedLocaleCode(),
            'additional_data',
        ]), $id);

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
     * Get all categories in tree format.
     */
    public function tree(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale'     => 'required|string',
            'selected'   => 'nullable|array',
            'selected.*' => 'string',
        ]);

        $selectedCodes = $validated['selected'] ?? [];

        $selectedCategories = $this->categoryRepository->findWhereIn('code', $selectedCodes);

        $allBranches = collect();

        foreach ($selectedCategories as $category) {
            if (! $category->parent) {
                continue;
            }

            $branches = $this->categoryRepository->getTreeBranchToParent($category, false);

            if ($branches && ! empty($branches)) {
                $allBranches[] = $branches->first();
            }
        }

        $categories = $this->categoryRepository->getRootCategories();

        return new JsonResponse([
            'data'          => $categories,
            'selected_tree' => $allBranches,
        ]);
    }

    /**
     * Fetch child categories for a given category ID.
     */
    public function children(): JsonResponse
    {
        $parentId = (int) request()->input('id');

        $categoryId = (int) (request()->input('category') ?? 0);

        $this->categoryRepository->findOrFail($parentId);

        if (request()->filled('page')) {
            return new JsonResponse(
                $this->categoryRepository->getChildCategoriesPaginated(
                    $parentId,
                    $categoryId,
                    (int) request()->input('page'),
                    (int) request()->input('limit', CategoryRepository::DEFAULT_PER_PAGE),
                )
            );
        }

        $childCategories = $this->categoryRepository->getChildCategories($parentId, $categoryId);

        return new JsonResponse($childCategories->toArray());
    }

    public function search(): JsonResponse
    {
        $locale = preg_replace('/[^A-Za-z_]/', '', (string) (request('locale') ?? core()->getRequestedLocaleCode()));

        $searchQuery = trim((string) request('query', ''));

        $query = $this->categoryRepository->getModel()->newQuery();

        if ($searchQuery !== '') {
            $query->where(function ($builder) use ($searchQuery, $locale) {
                $builder->where('additional_data->locale_specific->'.$locale.'->name', 'LIKE', '%'.$searchQuery.'%')
                    ->orWhere('code', 'LIKE', '%'.$searchQuery.'%');
            });
        }

        $page = max(self::DEFAULT_PAGE, (int) request('page', self::DEFAULT_PAGE));

        $paginator = $query->defaultOrder()->paginate(self::SEARCH_PER_PAGE, ['*'], 'page', $page);

        $results = $paginator->getCollection()->map(fn ($category) => [
            'id'    => $category->id,
            'code'  => $category->code,
            'label' => $category->additional_data['locale_specific'][$locale]['name'] ?? '['.$category->code.']',
        ])->values();

        return new JsonResponse([
            'data'     => $results,
            'page'     => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ]);
    }
}
