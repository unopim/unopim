<?php

namespace Webkul\Category\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Category\Contracts\CategoryField;
use Webkul\Core\Eloquent\Repository;

class CategoryFieldRepository extends Repository
{
    protected array $fieldWithOptions = [
        'select',
        'multiselect',
        'checkbox',
    ];

    /**
     * Create a new category field repository instance
     */
    public function __construct(Container $container, protected CategoryFieldOptionRepository $categoryFieldOptionRepository)
    {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return CategoryField::class;
    }

    /**
     * Create a new category field according to type and options
     *
     * @return CategoryField
     */
    public function create(array $data)
    {
        $categoryField = parent::create($data);

        if (
            ! in_array($categoryField->type, $this->fieldWithOptions)
            || ! isset($data['options'])
        ) {
            return $categoryField;
        }

        foreach ($data['options'] as $option) {
            $this->categoryFieldOptionRepository->create(array_merge($option, [
                'category_field_id' => $categoryField->id,
            ]));
        }

        return $categoryField;
    }

    /**
     * Update a category field in the database and its options if present.
     *
     * @param  int  $id
     * @return CategoryField
     */
    public function update(array $data, $id)
    {
        $categoryField = parent::update($data, $id);

        if (
            ! in_array($categoryField->type, $this->fieldWithOptions)
            || ! isset($data['options'])
        ) {
            return $categoryField;
        }

        foreach ($data['options'] as $optionId => $option) {
            if ($option['isNew'] == 'true') {
                $this->categoryFieldOptionRepository->create(array_merge([
                    'category_field_id' => $categoryField->id,
                ], $option));

                continue;
            }

            if ($option['isDelete'] == 'true') {
                $this->categoryFieldOptionRepository->delete($optionId);
            } else {
                $this->categoryFieldOptionRepository->update($option, $optionId);
            }
        }

        return $categoryField;
    }

    /**
     * Retrieves active category fields by section
     */
    public function getActiveCategoryFieldsBySection(string $section = 'left'): Collection
    {
        return $this->where(['status' => 1, 'section' => $section])->with(['translations', 'options', 'options.translations'])->orderBy('position')->get();
    }

    /**
     * Retrieves active category fields
     */
    public function getActiveCategoryFields(): Collection
    {
        return $this->where(['status' => 1])->orderBy('position')->get();
    }

    public function queryBuilder()
    {
        return $this->with(['translations']);
    }
}
