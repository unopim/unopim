<?php

namespace Webkul\Category\Repositories;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Webkul\Category\Contracts\Category;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Models\ProductProxy;

class CategoryRepository extends Repository
{
    const ADDITIONAL_VALUES_KEY = 'additional_data';

    const LOCALE_VALUES_KEY = 'locale_specific';

    const COMMON_VALUES_KEY = 'common';

    const DEFAULT_PAGE = 1;

    const DEFAULT_PER_PAGE = 100;

    /**
     * Hard ceiling on a one-shot descendant subtree fetch, so a click on a
     * near-root category with a huge branch can't pull tens of thousands
     * of rows into a single response.
     */
    const MAX_DESCENDANTS = 5000;

    public function __construct(Container $app, protected FileStorer $fileStorer)
    {
        parent::__construct($app);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Category::class;
    }

    /**
     * Get categories.
     *
     * @return void
     */
    public function getAll(array $params = [])
    {
        $queryBuilder = $this->query();

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'only_children':
                    $queryBuilder->whereNotNull('categories.parent_id');

                    break;
                case 'parent_id':
                    $queryBuilder->where('categories.parent_id', $value);

                    break;
            }
        }

        return $queryBuilder->paginate($params['limit'] ?? 10);
    }

    /**
     * Create category.
     *
     * @return Category
     */
    public function create(array $data, bool $withoutFormattingValues = false)
    {
        $category = $this->model->create($data);

        if (isset($data[self::ADDITIONAL_VALUES_KEY])) {
            /**
             * For csv or xls when values are already formatted
             */
            $data = $withoutFormattingValues
                ? $data
                : $this->prepareAdditionalData($data, $category);

            $category->additional_data = $data[self::ADDITIONAL_VALUES_KEY];

            $category->save();
        }

        return $category;
    }

    /**
     * Update category.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return Category
     */
    public function update(array $data, $id, $attribute = 'id', bool $withoutFormattingValues = false)
    {
        $category = $this->find($id);

        if (! empty($data[self::ADDITIONAL_VALUES_KEY])) {
            /**
             * For csv or xls when values are already formatted
             */
            $data = $withoutFormattingValues ? $data : $this->prepareAdditionalData($data, $category);

            $category->additional_data = $data[self::ADDITIONAL_VALUES_KEY];
        }

        $category->update($data);

        return $category;
    }

    /**
     * Specify category tree.
     *
     * @param  int  $id
     * @return Category
     */
    public function getCategoryTree($id = null)
    {
        return $id
            ? $this->model->where('id', '!=', $id)->get()->toTree()
            : $this->model->get()->toTree();
    }

    /**
     * Specify category tree.
     *
     * @param  int  $id
     * @return Collection
     */
    public function getCategoryTreeWithoutDescendant($id = null)
    {
        return $id
            ? $this->model->where('id', '!=', $id)->whereNotDescendantOf($id)->get()->toTree()
            : $this->model->get()->toTree();
    }

    /**
     * Retrieves the tree branch from the given category up to its parent categories.
     *
     * Only the path itself is loaded — the siblings at every level along it are
     * left to the lazy children endpoint, so a parent holding tens of thousands
     * of categories no longer hydrates its whole level to reveal one node.
     *
     * @param  Category  $category  The category instance to start from.
     * @param  bool  $present  Retained for backward compatibility; the present category is never part of the path.
     * @return \Kalnoy\Nestedset\Collection|null The branch of categories from the given category to its parent(s).
     */
    public function getTreeBranchToParent(Category $category, bool $present = true)
    {
        if (! $category->parent_id) {
            return;
        }

        $parent = $this->getModel()->newQuery()->find($category->parent_id, ['code']);

        if (! $parent) {
            return;
        }

        return $this->getPathNodes([$parent->code])
            ->toTree()
            ->values();
    }

    /**
     * Resolve breadcrumb paths (e.g. "Root / Child / Leaf") for the given category
     * ids using the nested-set `ancestors` relation.
     *
     * Runs a fixed number of queries regardless of tree depth or size (one to load
     * the categories, one to eager-load all their ancestors), so it stays cheap for
     * paginated listings instead of walking the whole tree.
     *
     * @param  int[]  $ids
     * @param  string|null  $locale  Locale the names are resolved in; the requested one when omitted.
     * @return array<int, string> Map of category id => breadcrumb path.
     */
    public function getBreadcrumbsForIds(array $ids, ?string $locale = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $ids))));

        if ($ids === []) {
            return [];
        }

        return $this->model
            ->whereIn('id', $ids)
            ->with(['ancestors' => fn ($query) => $query->defaultOrder()])
            ->get()
            ->mapWithKeys(fn ($category): array => [
                (int) $category->id => $category->ancestors
                    ->push($category)
                    ->map(fn ($node) => $this->resolveName($node, $locale))
                    ->implode(' / '),
            ])
            ->all();
    }

    /**
     * Name of a category in the given locale, falling back to the configured
     * locale and finally to the code so a path never renders with holes.
     */
    protected function resolveName(Category $category, ?string $locale = null): string
    {
        $names = $category->{self::ADDITIONAL_VALUES_KEY}[self::LOCALE_VALUES_KEY] ?? [];

        return $names[$locale ?? core()->getRequestedLocaleCode()]['name']
            ?? $names[config('app.fallback_locale')]['name']
            ?? '['.$category->code.']';
    }

    /**
     * Nodes needed to reveal the given categories inside a lazily loaded tree:
     * the categories themselves plus every ancestor, deduplicated.
     *
     * Ancestors are resolved one level at a time by primary key, so the query
     * count follows tree depth rather than the number of selected categories,
     * and no sibling is loaded — a level with thousands of children stays cheap
     * because the rest of it is fetched on demand when the branch is expanded.
     *
     * @param  string[]  $codes
     * @return \Kalnoy\Nestedset\Collection<int, \Webkul\Category\Models\Category>
     */
    public function getPathNodes(array $codes)
    {
        $columns = ['id', 'code', 'parent_id', '_lft', '_rgt', 'additional_data'];

        $nodes = $this->getModel()
            ->newQuery()
            ->whereIn('code', $codes)
            ->get($columns)
            ->keyBy('id');

        $pending = $nodes->pluck('parent_id')->filter()->unique()->diff($nodes->keys());

        while ($pending->isNotEmpty()) {
            $ancestors = $this->getModel()
                ->newQuery()
                ->whereIn('id', $pending->all())
                ->get($columns);

            if ($ancestors->isEmpty()) {
                break;
            }

            foreach ($ancestors as $ancestor) {
                $nodes[$ancestor->id] = $ancestor;
            }

            $pending = $ancestors->pluck('parent_id')->filter()->unique()->diff($nodes->keys());
        }

        return $nodes->values();
    }

    /**
     * Get root categories.
     *
     * @return Collection
     */
    public function getRootCategories()
    {
        return $this->getModel()
            ->whereNull('parent_id')
            ->get();
    }

    /**
     * Get child categories.
     *
     * @return Collection
     */
    public function getChildCategories(int $parentId, int $categoryId = 0)
    {
        $query = $this->getModel()->where('parent_id', $parentId);

        if ($categoryId !== 0) {
            $query->where('id', '!=', $categoryId);
        }

        return $query->get()->toTree();
    }

    /**
     * @return array{data: \Illuminate\Database\Eloquent\Collection<int, \Webkul\Category\Models\Category>, page: int, has_more: bool, total: int}
     */
    public function getChildCategoriesPaginated(int $parentId, int $categoryId = 0, int $page = self::DEFAULT_PAGE, int $limit = self::DEFAULT_PER_PAGE): array
    {
        $page = max($page, self::DEFAULT_PAGE);
        $limit = max($limit, 1);

        $query = $this->getModel()->where('parent_id', $parentId);

        if ($categoryId !== 0) {
            $query->where('id', '!=', $categoryId);
        }

        $total = (clone $query)->count();

        $children = $query->defaultOrder()
            ->forPage($page, $limit)
            ->get();

        return [
            'data'     => $children,
            'page'     => $page,
            'has_more' => ($page * $limit) < $total,
            'total'    => $total,
        ];
    }

    /**
     * Descendant count read straight off the category's own _lft/_rgt
     * span — no query against the descendants themselves, just the row
     * already fetched for validation.
     */
    public function countDescendants(int $id): int
    {
        $category = $this->model->findOrFail($id);

        return (int) (($category->_rgt - $category->_lft - 1) / 2);
    }

    /**
     * The full descendant subtree of a category in one nested-set range
     * query, hydrated back into a tree so a cascading select can both
     * select and render every level in a single pass — no per-level
     * fetch, so nothing pops open staggered as separate requests resolve.
     *
     * Passing the loaded model rather than its id keeps kalnoy/nestedset
     * from re-querying the node's own _lft/_rgt bounds a second time.
     *
     * @return Collection
     */
    public function getDescendantTree(int $id)
    {
        $category = $this->model->findOrFail($id);

        return $this->model->descendantsOf($category)->toTree($id);
    }

    /**
     * get visible category tree.
     *
     * @param  int  $id
     * @return Collection
     */
    public function getVisibleCategoryTree($id = null)
    {
        return $id
            ? $this->model->descendantsAndSelf($id)->toTree($id)
            : $this->model->get()->toTree();
    }

    /**
     * Get partials.
     *
     * @param  array|null  $columns
     */
    public function getPartial($columns = null): array
    {
        $categories = $this->model->all();

        $trimmed = [];

        foreach ($categories as $key => $category) {
            if (! empty($category->name)) {
                $trimmed[$key] = [
                    'id'   => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }
        }

        return $trimmed;
    }

    /**
     * Set same value to all locales in category.
     *
     * To Do: Move column from the `category_translations` to `category` table. And remove
     * this created method.
     *
     * @param  string  $attributeNames
     */
    private function setSameAttributeValueToAllLocale(array $data, ...$attributeNames): array
    {
        $requestedLocale = core()->getRequestedLocaleCode();

        $model = app()->make($this->model());

        foreach ($attributeNames as $attributeName) {
            foreach (core()->getAllActiveLocales() as $locale) {
                if ($requestedLocale == $locale->code) {
                    foreach ($model->translatedAttributes as $attribute) {
                        if ($attribute === $attributeName) {
                            $data[$locale->code][$attribute] = $data[$requestedLocale][$attribute] ?? $data[$data['locale']][$attribute];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Modifies the category field values in category data
     *
     * Reference has been used in this to avoid redundant access of array
     *
     * Additional Values Format
     *
     * 'additional_values' => [
     *     'common' => [
     *          'categoryFieldCode' => 'value',
     *      ],
     *      'locale_specific' => [
     *          'localeCode' => [
     *              'categoryFieldCode' => 'value',
     *          ],
     *      ]
     * ]
     */
    protected function prepareAdditionalData(array $data, Category $category): array
    {
        if (empty($data[self::ADDITIONAL_VALUES_KEY])) {
            return $data;
        }

        $currentLocaleCode = core()->getRequestedLocaleCode();

        /**
         * when the array key for common or locale is not present null is returned
         * but refernce is still maintained
         */
        $commonValues = &$data[self::ADDITIONAL_VALUES_KEY][self::COMMON_VALUES_KEY];

        $localeValues = &$data[self::ADDITIONAL_VALUES_KEY][self::LOCALE_VALUES_KEY];

        if (! empty($localeValues[$currentLocaleCode])) {
            $localeValues[$currentLocaleCode] = $this->processAdditionalDataValues(
                categoryId: $category->id,
                values: $localeValues[$currentLocaleCode],
                categoryValues: ($category->additional_data[self::LOCALE_VALUES_KEY][$currentLocaleCode] ?? [])
            );
        }

        if (isset($category->additional_data[self::LOCALE_VALUES_KEY])) {
            $localeValues = empty($localeValues)
                ? $category->additional_data[self::LOCALE_VALUES_KEY]
                : array_merge($category->additional_data[self::LOCALE_VALUES_KEY], $localeValues);
        }

        $localeValues = is_array($localeValues)
            ? array_filter($localeValues)
            : $localeValues;

        if (! empty($commonValues)) {
            $commonValues = $this->processAdditionalDataValues(
                categoryId: $category->id,
                values: $commonValues,
                categoryValues: ($category->additional_data[self::COMMON_VALUES_KEY] ?? [])
            );
        } elseif (isset($category->additional_data[self::COMMON_VALUES_KEY])) {
            $commonValues = $category->additional_data[self::COMMON_VALUES_KEY];
        }

        if (empty($commonValues)) {
            unset($data[self::ADDITIONAL_VALUES_KEY][self::COMMON_VALUES_KEY]);
        }

        if (empty($localeValues)) {
            unset($data[self::ADDITIONAL_VALUES_KEY][self::LOCALE_VALUES_KEY]);
        }

        ksort($data[self::ADDITIONAL_VALUES_KEY], SORT_NATURAL);

        return $data;
    }

    /**
     * process values by value type like files and images
     */
    protected function processAdditionalDataValues(int $categoryId, array $values, array $categoryValues = []): array
    {
        $values = array_filter(
            $categoryValues === []
                ? $values
                : array_merge($categoryValues, $values)
        );

        foreach ($values as $field => $fieldValue) {
            if (is_array($fieldValue) && current($fieldValue) instanceof UploadedFile) {
                $fieldValue = current($fieldValue);
            }

            if ($fieldValue instanceof UploadedFile) {
                $values[$field] = $this->fileStorer->store(
                    path: 'category'.DIRECTORY_SEPARATOR.$categoryId.DIRECTORY_SEPARATOR.$field,
                    file: $fieldValue,
                    options: [FileStorer::HASHED_FOLDER_NAME_KEY => true]
                );

                continue;
            }

            if (is_array($fieldValue)) {
                $values[$field] = implode(',', $fieldValue);
            }
        }

        return $values;
    }

    public function queryBuilder()
    {
        return $this->with(['parent_category']);

    }

    /**
     * Determine whether any product is assigned to the category or to one of its descendants,
     * which a delete of the category removes with it.
     */
    public function isUsedByProducts(int $categoryId): bool
    {
        return $this->isAnyUsedByProducts([$categoryId]);
    }

    /**
     * Determine whether any product is assigned to one of the given categories or their descendants.
     *
     * @param  array<int, int|string>  $categoryIds
     */
    public function isAnyUsedByProducts(array $categoryIds): bool
    {
        $codes = array_keys($this->subtreeRootsByCode($categoryIds));

        foreach (array_chunk($codes, 100) as $codeChunk) {
            if ($this->productsAssignedToAny($codeChunk)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the ids of the given categories that any product is assigned to, directly or through a descendant.
     *
     * @param  array<int, int|string>  $categoryIds
     * @return array<int, int>
     */
    public function usedByProducts(array $categoryIds): array
    {
        $rootsByCode = $this->subtreeRootsByCode($categoryIds);

        $pending = array_fill_keys(array_merge([], ...array_values($rootsByCode)), true);

        $usedIds = [];

        foreach (array_chunk(array_keys($rootsByCode), 100) as $codeChunk) {
            $this->productsAssignedToAny($codeChunk)
                ->select('id', 'values->categories as categories')
                ->lazyById(200)
                ->each(function (object $product) use ($rootsByCode, &$pending, &$usedIds): bool {
                    $codes = json_decode((string) $product->categories, true);

                    foreach (is_array($codes) ? $codes : [] as $code) {
                        foreach (is_string($code) ? ($rootsByCode[$code] ?? []) : [] as $rootId) {
                            $usedIds[$rootId] = $rootId;

                            unset($pending[$rootId]);
                        }
                    }

                    return $pending !== [];
                });

            if ($pending === []) {
                break;
            }
        }

        return array_values($usedIds);
    }

    /**
     * Map the code of every category in the given subtrees to the ids of the given categories that contain it.
     *
     * @param  array<int, int|string>  $categoryIds
     * @return array<string, array<int, int>>
     */
    protected function subtreeRootsByCode(array $categoryIds): array
    {
        $rootsByCode = [];

        $roots = $this->model->newQuery()
            ->whereIn('id', array_unique($categoryIds))
            ->get(['id', '_lft', '_rgt']);

        foreach ($roots->chunk(100) as $rootChunk) {
            $nodes = $this->model->newQuery()
                ->where(function ($query) use ($rootChunk): void {
                    foreach ($rootChunk as $root) {
                        $query->orWhereBetween('_lft', [$root->_lft, $root->_rgt]);
                    }
                })
                ->get(['code', '_lft']);

            foreach ($nodes as $node) {
                foreach ($rootChunk as $root) {
                    if ($node->_lft >= $root->_lft && $node->_lft <= $root->_rgt) {
                        $rootsByCode[$node->code][] = (int) $root->id;
                    }
                }
            }
        }

        return $rootsByCode;
    }

    /**
     * Build a query for the products assigned to any of the given category codes.
     *
     * @param  array<int, string>  $codes
     */
    protected function productsAssignedToAny(array $codes): Builder
    {
        return ProductProxy::query()
            ->toBase()
            ->where(function (Builder $query) use ($codes): void {
                foreach ($codes as $code) {
                    $query->orWhereJsonContains('values->categories', $code);
                }
            });
    }

    /**
     * The products.
     */
    public function getProducts(string $code)
    {
        return ProductProxy::query()->whereJsonContains('values->categories', $code)->get();
    }
}
