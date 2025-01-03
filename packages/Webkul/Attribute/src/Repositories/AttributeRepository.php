<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Eloquent\Repository;

class AttributeRepository extends Repository
{
    protected $attributes = [];

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeOptionRepository $attributeOptionRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Attribute::class;
    }

    /**
     * Create attribute.
     *
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function create(array $data)
    {
        $validatedData = $this->validateUserInput($data);

        $options = $validatedData['options'] ?? [];

        unset($validatedData['options']);

        $attribute = $this->model->create($validatedData);

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox']) && $options) {
            foreach ($options as $optionInputs) {
                $this->attributeOptionRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                ], $optionInputs));
            }
        }

        return $attribute;
    }

    /**
     * Update attribute.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $validatedData = $this->validateUserInput($data);

        $attribute = $this->find($id);

        $attribute->update($validatedData);

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox']) && isset($data['options'])) {
            foreach ($data['options'] as $optionId => $optionInputs) {
                if ($optionInputs['isNew'] == 'true') {
                    $this->attributeOptionRepository->create(array_merge([
                        'attribute_id' => $attribute->id,
                    ], $optionInputs));
                } else {
                    if ($optionInputs['isDelete'] == 'true') {
                        $this->attributeOptionRepository->delete($optionId);
                    } else {
                        $this->attributeOptionRepository->update($optionInputs, $optionId);
                    }
                }
            }
        }

        return $attribute;
    }

    /**
     * Validate user input.
     *
     * @param  array  $data
     * @return array
     */
    public function validateUserInput($data)
    {
        if (isset($data['type']) && in_array($data['type'], ['select', 'checkbox', 'multiselect', 'boolean', 'image', 'file'])) {
            unset($data['is_unique']);
        }

        return $data;
    }

    /**
     * Get product default attributes.
     *
     * @param  array  $codes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductDefaultAttributes($codes = null)
    {
        $attributeColumns = [
            'id',
            'code',
            'value_per_channel',
            'value_per_locale',
            'type',
        ];

        if (
            ! is_array($codes)
            && ! $codes
        ) {
            return $this->findWhereIn('code', [
                'name',
                'description',
                'short_description',
                'url_key',
                'price',
                'special_price',
                'special_price_from',
                'special_price_to',
                'status',
            ], $attributeColumns);
        }

        if (in_array('*', $codes)) {
            return $this->all($attributeColumns);
        }

        return $this->findWhereIn('code', $codes, $attributeColumns);
    }

    /**
     * Get family attributes.
     *
     * @param  \Webkul\Attribute\Contracts\AttributeFamily  $attributeFamily
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function getFamilyAttributes($attributeFamily)
    {
        if (array_key_exists($attributeFamily->id, $this->attributes)) {
            return $this->attributes[$attributeFamily->id];
        }

        return $this->attributes[$attributeFamily->id] = $attributeFamily->customAttributes;
    }

    /**
     * Get partials.
     *
     * @return array
     */
    public function getPartial()
    {
        $attributes = $this->model->all();

        $trimmed = [];

        foreach ($attributes as $key => $attribute) {
            if (
                $attribute->code != 'tax_category_id'
                && (
                    in_array($attribute->type, ['select', 'multiselect'])
                    || $attribute->code == 'sku'
                )
            ) {
                array_push($trimmed, [
                    'id'      => $attribute->id,
                    'name'    => $attribute->admin_name,
                    'type'    => $attribute->type,
                    'code'    => $attribute->code,
                    'options' => $attribute->options,
                ]);
            }
        }

        return $trimmed;
    }

    public function findVariantOption(string $attribute, string $option)
    {
        return $this->queryBuilder()->scopeQuery(function ($query) use ($attribute, $option) {
            $query->join('attribute_options', 'attributes.id', '=', 'attribute_options.attribute_id')
                ->whereIn('attributes.type', AttributeFamily::ALLOWED_VARIANT_OPTION_TYPES)
                ->where('attributes.code', $attribute)
                ->where('attribute_options.code', $option);

            return $query;
        })->get();
    }

    /**
     * This function returns a query builder instance for the Attribute model.
     * It eager loads the 'translations' relationship for the Attribute.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilder()
    {
        return $this->with(['translations']);
    }

    /**
     * Get Attribute list by search query
     */
    public function getAttributeListBySearch(string $search, array $columns = ['*']): array
    {
        return DB::table('attributes')
            ->select($columns)
            ->leftJoin('attribute_translations as attribute_name', function ($join) {
                $join->on('attribute_name.attribute_id', '=', 'attributes.id')
                    ->where('attribute_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->where(function ($query) use ($search) {
                $query->where('attributes.code', 'LIKE', '%'.$search.'%')
                    ->orWhere('attribute_name.name', 'LIKE', '%'.$search.'%');
            })
            ->get()
            ->toArray();
    }
}
