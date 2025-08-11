<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CategoryDataGrid::class)->toJson();
        }

        return view('admin::catalog.categories.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
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
     * @param  \Illuminate\Support\Collection  $categories  Collection of category objects.
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
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $categoryRequest)
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
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
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
     *
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $categoryRequest, int $id)
    {
        Event::dispatch('catalog.category.update.before', $id);

        if (! empty($categoryRequest->input('parent_id')) && $this->isRelatedToChannel($id)) {
            session()->flash('error', trans('admin::app.catalog.categories.can-not-update'));

            return redirect()->route('admin.catalog.categories.edit', ['id' => $id]);
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

                return new JsonResponse(['message' => trans('admin::app.catalog.categories.delete-category-root')], 400);
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
                ], 500);
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tree(Request $request)
    {
        $validated = $request->validate([
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
        $id = (int) request()->get('id');

        $categoryId = request()->get('category') ?? 0;

        $this->categoryRepository->findOrFail($id);

        $childCategories = $this->categoryRepository->getChildCategories($id, $categoryId);

        return new JsonResponse($childCategories->toArray());
    }

    /**
     * Result of search customer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $results = [];

        $categories = $this->categoryRepository->scopeQuery(function ($query) {
            return $query
                ->select('categories.*')
                ->orderBy('created_at', 'desc');
        })->paginate(10);

        return response()->json($categories);
    }
}
