<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Eloquent\Repository;

class AttributeRepository extends Repository
{
    protected array $attributes = [];

    /**
     * Create a new repository instance.
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
     */
    #[\Override]
    public function create(array $data): Attribute
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
     */
    #[\Override]
    public function update(array $data, $id, $attribute = 'id'): Attribute
    {
        $validatedData = $this->validateUserInput($data);

        $attribute = $this->find($id);

        if ($attribute->code === 'sku' && isset($data['is_filterable']) && $data['is_filterable'] === 0) {
            $data['is_filterable'] = 1;
        }

        $attribute->update($validatedData);

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox']) && isset($data['options'])) {
            foreach ($data['options'] as $optionId => $optionInputs) {
                if ($optionInputs['isNew'] == 'true') {
                    if (empty($optionInputs['code'])) {
                        $optionInputs['code'] = 'option_'.strtolower(Str::random(8));
                    }
                    $this->attributeOptionRepository->create(array_merge([
                        'attribute_id' => $attribute->id,
                    ], $optionInputs));
                } elseif ($optionInputs['isDelete'] == 'true') {
                    $this->attributeOptionRepository->delete($optionId);
                } else {
                    $this->attributeOptionRepository->update($optionInputs, $optionId);
                }
            }
        }

        return $attribute;
    }

    /**
     * Validate user input.
     */
    public function validateUserInput(array $data): array
    {
        if (isset($data['type']) && $data['type'] !== 'text') {
            unset($data['is_unique']);
        }

        // Cast boolean fields to 0/1 — unchecked checkboxes send null/empty
        // which violates PostgreSQL NOT NULL constraints.
        foreach (['is_required', 'is_unique', 'enable_wysiwyg', 'is_filterable', 'ai_translate'] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $data[$boolField] = (int) (bool) $data[$boolField];
            }
        }

        return $data;
    }

    /**
     * Get product default attributes.
     */
    public function getProductDefaultAttributes(?array $codes = null): Collection
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
     * @return Attribute
     */
    public function getFamilyAttributes(AttributeFamily $attributeFamily): mixed
    {
        if (array_key_exists($attributeFamily->id, $this->attributes)) {
            return $this->attributes[$attributeFamily->id];
        }

        return $this->attributes[$attributeFamily->id] = $attributeFamily->customAttributes;
    }

    /**
     * Get partials.
     */
    public function getPartial(): array
    {
        $attributes = $this->model->all();

        $trimmed = [];

        foreach ($attributes as $attribute) {
            if ($attribute->code != 'tax_category_id'
            && (
                in_array($attribute->type, ['select', 'multiselect'])
                || $attribute->code == 'sku'
            )) {
                $trimmed[] = [
                    'id'      => $attribute->id,
                    'name'    => $attribute->admin_name,
                    'type'    => $attribute->type,
                    'code'    => $attribute->code,
                    'options' => $attribute->options,
                ];
            }
        }

        return $trimmed;
    }

    public function findVariantOption(string $attribute, string $option): Collection
    {
        return $this->queryBuilder()->scopeQuery(function (Builder $query) use ($attribute, $option) {
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
     */
    public function queryBuilder(): static
    {
        return $this->with(['translations']);
    }

    /**
     * Get Attribute list by search query
     */
    public function getAttributeListBySearch(string $search, array $columns = ['*'], array $excludeTypes = []): array
    {
        // Resolve ambiguous columns — `name` lives on attribute_translations, not attributes.
        $resolvedColumns = array_map(function (string $col) {
            if ($col === '*') {
                return 'attributes.*';
            }
            if ($col === 'name') {
                return 'attribute_name.name as name';
            }

            return str_contains($col, '.') ? $col : 'attributes.'.$col;
        }, $columns);

        $query = DB::table('attributes')
            ->select($resolvedColumns)
            ->leftJoin('attribute_translations as attribute_name', function (JoinClause $join) {
                $join->on('attribute_name.attribute_id', '=', 'attributes.id')
                    ->where('attribute_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->where(function (QueryBuilder $query) use ($search) {
                $query->where('attributes.code', 'LIKE', '%'.$search.'%')
                    ->orWhere('attribute_name.name', 'LIKE', '%'.$search.'%');
            });

        if ($excludeTypes !== []) {
            $query->whereNotIn('attributes.type', $excludeTypes);
        }

        return $query->get()->toArray();
    }
}
