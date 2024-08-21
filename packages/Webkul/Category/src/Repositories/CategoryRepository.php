<?php

namespace Webkul\Category\Repositories;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\UploadedFile;
use Webkul\Category\Contracts\Category;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Models\ProductProxy;

class CategoryRepository extends Repository
{
    const ADDITIONAL_VALUES_KEY = 'additional_data';

    const LOCALE_VALUES_KEY = 'locale_specific';

    const COMMON_VALUES_KEY = 'common';

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
     * @return \Webkul\Category\Contracts\Category
     */
    public function create(array $data, bool $withoutFormattingValues = false)
    {
        $category = $this->model->create($data);

        if (isset($data[self::ADDITIONAL_VALUES_KEY])) {
            /**
             * For csv or xls when values are already formatted
             */
            $data = $withoutFormattingValues ? $data : $this->prepareAdditionalData($data, $category);

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
     * @return \Webkul\Category\Contracts\Category
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
     * @return \Webkul\Category\Contracts\Category
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
     * @return \Illuminate\Support\Collection
     */
    public function getCategoryTreeWithoutDescendant($id = null)
    {
        return $id
            ? $this->model->where('id', '!=', $id)->whereNotDescendantOf($id)->get()->toTree()
            : $this->model->get()->toTree();
    }

    /**
     * Get root categories.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRootCategories()
    {
        return $this->getModel()->where('parent_id', null)->get();
    }

    /**
     * Get child categories.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getChildCategories($parentId)
    {
        return $this->getModel()->where('parent_id', $parentId)->get();
    }

    /**
     * get visible category tree.
     *
     * @param  int  $id
     * @return \Illuminate\Support\Collection
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
     * @return array
     */
    public function getPartial($columns = null)
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
     * @return array
     */
    private function setSameAttributeValueToAllLocale(array $data, ...$attributeNames)
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
            $localeValues = ! empty($localeValues)
                ? array_merge($category->additional_data[self::LOCALE_VALUES_KEY], $localeValues)
                : $category->additional_data[self::LOCALE_VALUES_KEY];
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
            ! empty($categoryValues)
                ? array_merge($categoryValues, $values)
                : $values
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
     * The products.
     */
    public function getProducts(string $code)
    {
        return ProductProxy::query()->whereJsonContains('values->categories', $code)->get();
    }
}
