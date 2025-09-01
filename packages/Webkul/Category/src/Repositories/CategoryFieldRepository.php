<?php

namespace Webkul\Category\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                $sequence = $this->model->getTable().'_id_seq';
                DB::statement("SELECT setval('{$sequence}', (SELECT COALESCE(MAX(id), 0) + 1 FROM {$this->model->getTable()}), false)");
                break;

            case 'mysql':
            default:
                break;
        }

        $categoryField = parent::create($data);

        if (
            ! in_array($categoryField->type, $this->fieldWithOptions)
            || ! isset($data['options'])
        ) {
            return $categoryField;
        }

        if ($driver === 'pgsql') {
            $optionTable = $this->categoryFieldOptionRepository->getModel()->getTable();
            $optionSeq = $optionTable.'_id_seq';
            DB::statement("SELECT setval('{$optionSeq}', (SELECT COALESCE(MAX(id), 0) + 1 FROM {$optionTable}), false)");
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

    /**
     * Get Category field list by search query
     */
    public function getCategoryFieldListBySearch(string $search, array $columns = ['*'], array $excludeTypes = []): array
    {
        $query = DB::table('category_fields')
            ->select($columns)
            ->leftJoin('category_field_translations as requested_category_field_translation', function ($join) {
                $join->on('requested_category_field_translation.category_field_id', '=', 'category_fields.id')
                    ->where('requested_category_field_translation.locale', '=', core()->getRequestedLocaleCode());
            })
            ->where(function ($query) use ($search) {
                $query->where('category_fields.code', 'LIKE', '%'.$search.'%')
                    ->orWhere('requested_category_field_translation.name', 'LIKE', '%'.$search.'%');
            });

        if ($excludeTypes) {
            $query->whereNotIn('category_fields.type', $excludeTypes);
        }

        return $query->get()->toArray();
    }
}
