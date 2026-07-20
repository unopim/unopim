<?php

namespace Webkul\Product\Type;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Admin\Validations\ConfigurableUniqueSku;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class Configurable extends AbstractType
{
    /**
     * These are the types which can be fillable when generating variant.
     *
     * @var array
     */
    protected $fillableTypes = [
        'sku',
        'name',
        'url_key',
        'short_description',
        'description',
        'price',
        'weight',
        'status',
        'tax_category_id',
    ];

    /**
     * Has child products i.e. variants.
     *
     * @var bool
     */
    protected $hasVariants = true;

    /**
     * Attribute stored bu their code.
     *
     * @var bool
     */
    protected $attributesByCode = [];

    /**
     * Attribute stored bu their id.
     *
     * @var bool
     */
    protected $attributesById = [];

    /**
     * Get default variant.
     *
     * @return Product
     */
    public function getDefaultVariant()
    {
        // Excludes `variant_group` nodes: they are internal grouping records,
        // not a leaf variant a storefront/API consumer can render as "the"
        // default variant.
        return $this->product->variants()
            ->where('type', '!=', 'variant_group')
            ->find($this->getDefaultVariantId());
    }

    /**
     * Get default variant id.
     *
     * @return int
     */
    public function getDefaultVariantId()
    {
        return $this->product->additional['default_variant_id'] ?? null;
    }

    /**
     * Set default variant id.
     *
     * @param  int  $defaultVariantId
     */
    public function setDefaultVariantId($defaultVariantId): void
    {
        $this->product->additional = array_merge($this->product->additional ?? [], [
            'default_variant_id' => $defaultVariantId,
        ]);
    }

    /**
     * Update default variant id if present in request.
     */
    public function updateDefaultVariantId(): void
    {
        if (! $defaultVariantId = request()->input('default_variant_id')) {
            return;
        }

        $this->setDefaultVariantId($defaultVariantId);

        $this->product->save();
    }

    /**
     * Create configurable product.
     *
     * @return \Webkul\Product\Contracts\Product
     */
    public function create(array $data)
    {
        $product = $this->productRepository->getModel()->create($data);

        $product->values = [self::COMMON_VALUES_KEY => ['sku' => $product->sku]];

        if (! empty($data['variant_structure_id'])) {
            $product->variant_structure_id = $data['variant_structure_id'];
        }

        if (! isset($data['super_attributes'])) {
            return $product;
        }

        foreach ($data['super_attributes'] as $attributeCode) {
            $attribute = $this->attributesByCode[$attributeCode] ?? null;

            if (empty($attribute)) {
                $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
            }

            $product->super_attributes()->attach($attribute->id);
        }

        $product->save();

        return $product;
    }

    /**
     * Update configurable product.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Product\Contracts\Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = parent::update($data, $id, $attribute);

        $this->updateDefaultVariantId();

        if (request()->route()?->getName() == 'admin.catalog.products.mass_update') {
            return $product;
        }

        $productSuperAttributes = $product->super_attributes;

        $uniqueAttributes = $this->getUniqueAttributes();

        if ($product->variantStructure?->levels === 2 && isset($data['variant_groups'])) {
            return $this->updateVariantGroups($product, $data, $productSuperAttributes, $uniqueAttributes);
        }

        $previousVariantIds = $product->variants->pluck('id');

        if (isset($data['variants'])) {
            foreach ($data['variants'] as $variantId => $variantData) {
                if (Str::contains($variantId, 'variant_')) {
                    $variant = $this->createVariant($product, $productSuperAttributes, $variantData, $uniqueAttributes);

                    Event::dispatch('catalog.product.create.after', $variant);
                } else {
                    if (is_numeric($index = $previousVariantIds->search($variantId))) {
                        $previousVariantIds->forget($index);
                    }

                    $variantData['super_attributes'] = $productSuperAttributes;

                    $this->updateVariant($variantData, $variantId);
                }
            }
        }

        foreach ($previousVariantIds as $variantId) {
            $this->productRepository->delete($variantId);
        }

        return $product;
    }

    /**
     * Save the 3-tier `variant_groups` payload for a 2-level variant
     * structure: create/update each `variant_group` node and its nested
     * `simple` variants, then prune groups (and their orphaned children) no
     * longer present in the payload. Mirrors the flat `variants` pruning in
     * `update()`, one level deeper.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @param  Collection  $productSuperAttributes
     * @return \Webkul\Product\Contracts\Product
     */
    protected function updateVariantGroups($product, array $data, $productSuperAttributes, Collection|array $uniqueAttributes = [])
    {
        $planner = resolve(VariantStructurePlanner::class);

        $groupAxis = $planner->axisCodesByLevel($product->variantStructure)['level_1'][0] ?? null;

        $existingGroups = $product->variants()
            ->where('type', 'variant_group')
            ->with('variants')
            ->get()
            ->keyBy('id');

        foreach ($data['variant_groups'] as $groupKey => $groupData) {
            if (Str::contains($groupKey, 'group_')) {
                $group = $this->createVariantGroup($product, array_merge($groupData, [
                    'group_axis_code' => $groupAxis,
                ]));
            } else {
                if (! $group = $existingGroups->pull($groupKey)) {
                    continue;
                }

                $this->updateVariantGroupValues($group, $groupAxis, $groupData);
            }

            $previousGroupVariantIds = $group->variants->pluck('id');

            foreach ($groupData['variants'] ?? [] as $variantId => $variantData) {
                if (Str::contains($variantId, 'variant_')) {
                    $variantData['parent_id'] = $group->id;

                    $variant = $this->createVariant($product, $productSuperAttributes, $variantData, $uniqueAttributes);

                    Event::dispatch('catalog.product.create.after', $variant);
                } else {
                    if (is_numeric($index = $previousGroupVariantIds->search($variantId))) {
                        $previousGroupVariantIds->forget($index);
                    }

                    $variantData['super_attributes'] = $productSuperAttributes;

                    $this->updateVariant($variantData, $variantId);
                }
            }

            foreach ($previousGroupVariantIds as $variantId) {
                $this->productRepository->delete($variantId);
            }
        }

        foreach ($existingGroups as $orphan) {
            $orphan->variants->each(fn ($child) => $this->productRepository->delete($child->id));

            $this->productRepository->delete($orphan->id);
        }

        return $product;
    }

    /**
     * Merge submitted sub_parent/common values into an existing variant_group
     * node. The L1 axis option itself (`group_axis_option`) is set once at
     * creation time and is not currently re-editable through this path.
     */
    protected function updateVariantGroupValues(Product $group, ?string $groupAxis, array $groupData): void
    {
        $values = $group->values;

        foreach ($groupData['group_values'] ?? [] as $code => $value) {
            $values[self::COMMON_VALUES_KEY][$code] = $value;
        }

        $group->values = $values;
        $group->save();
    }

    /**
     * Create a variant_group node under $product, holding the L1 axis option
     * and any sub_parent values shared by its child variants.
     */
    public function createVariantGroup($product, array $groupData): Product
    {
        $group = $this->productRepository->getModel()->create([
            'parent_id'           => $product->id,
            'type'                => 'variant_group',
            'attribute_family_id' => $product->attribute_family_id,
            'sku'                 => $groupData['sku'],
        ]);

        $values = [self::COMMON_VALUES_KEY => []];

        if (! empty($groupData['group_axis_code'])) {
            $values[self::COMMON_VALUES_KEY][$groupData['group_axis_code']] = $groupData['group_axis_option'];
        }

        foreach ($groupData['group_values'] ?? [] as $code => $value) {
            $values[self::COMMON_VALUES_KEY][$code] = $value;
        }

        $values[self::COMMON_VALUES_KEY]['sku'] = $groupData['sku'];

        $group->values = $values;
        $group->save();

        return $group;
    }

    /**
     * Create variant.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @param  array  $productSuperAttributes
     * @return \Webkul\Product\Contracts\Product
     */
    public function createVariant($product, $productSuperAttributes, array $data = [], Collection|array $uniqueAttributes = [])
    {
        $variant = $this->productRepository->getModel()->create([
            'parent_id'           => $data['parent_id'] ?? $product->id,
            'type'                => 'simple',
            'attribute_family_id' => $product->attribute_family_id,
            'sku'                 => $data['sku'],
        ]);

        // Inheritance is resolved at read time (see VariantValueResolver); a
        // variant stores only what it owns - its axis options and sku - instead
        // of copying the parent's values. This removes storage/write duplication
        // and keeps a single source of truth on the ancestor chain.
        $variantValues = [];

        foreach ($productSuperAttributes as $attribute) {
            $attrCode = $attribute->code;

            $suppliedCommonValues = $data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY] ?? [];

            // In a 2-level structure the axis fixed at the group level (e.g. color)
            // is not present in the leaf variant's payload - only the axis that
            // differentiates it within the group (e.g. size) is. Skip that axis
            // instead of failing; it resolves from the ancestor chain at read
            // time (see VariantValueResolver). Legacy 1-level callers (no
            // parent_id / no variant structure) get no such pass - a missing
            // axis there still fails loudly below on a malformed payload.
            if (
                ! array_key_exists($attrCode, $suppliedCommonValues)
                && ! empty($data['parent_id'])
                && $product->variant_structure_id
            ) {
                continue;
            }

            $variantValues[self::COMMON_VALUES_KEY][$attrCode] = $data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY][$attrCode];
        }

        $variantValues[self::COMMON_VALUES_KEY]['sku'] = $data['sku'];

        $variant->values = $variantValues;

        $variant->save();

        return $variant;
    }

    /**
     * Update variant.
     *
     * @param  int  $id
     * @return \Webkul\Product\Contracts\Product
     */
    public function updateVariant(array $data, $id)
    {
        $variant = $this->productRepository->find($id);

        $variantValues = $variant->values;

        $suppliedCommonValues = $data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY] ?? [];

        // Same scoped exception as createVariant(): in a 2-level structure the
        // axis fixed at the group level is not resubmitted on every leaf
        // variant update - only the axis that differentiates it within the
        // group is. Skip it here too, but only when this variant is actually
        // group-owned (or the configurable is a 2-level structure); legacy
        // 1-level updates must keep failing loudly on a malformed payload.
        $isTwoLevelVariant = $variant->parent?->type === 'variant_group'
            || ($this->product->variant_structure_id && $this->product->variantStructure?->levels === 2);

        foreach ($data['super_attributes'] ?? [] as $attribute) {
            $attrCode = $attribute->code;

            if (
                ! array_key_exists($attrCode, $suppliedCommonValues)
                && $isTwoLevelVariant
            ) {
                continue;
            }

            $variantValues[self::COMMON_VALUES_KEY][$attrCode] = $suppliedCommonValues[$attrCode];
        }

        $variantValues[self::COMMON_VALUES_KEY]['sku'] = $data['sku'];

        $variant->values = $variantValues;

        $variant->update(['sku' => $data['sku']]);

        return $variant;
    }

    /**
     * Copy relationships.
     *
     * @param  Product  $product
     * @return void
     */
    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.skipAttributesOnCopy', []);

        if (
            in_array('super_attributes', $attributesToSkip)
            || in_array('variants', $attributesToSkip)
        ) {
            return;
        }

        foreach ($this->product->super_attributes as $superAttribute) {
            $product->super_attributes()->save($superAttribute);
        }

        foreach ($this->product->variants as $variant) {
            $newVariant = $variant->getTypeInstance()->copy();

            $newVariant->parent_id = $product->id;

            $newVariant->save();
        }
    }

    /**
     * Returns children ids.
     *
     * @return array
     */
    public function getChildrenIds()
    {
        return $this->product->variants()->pluck('id')->toArray();
    }

    /**
     * Return validation rules.
     */
    public function getTypeValidationRules(): array
    {
        return [
            'variants.*.sku'    => [
                'required',
                new ConfigurableUniqueSku($this->getChildrenIds()),
            ],
        ];
    }

    /**
     * Compare options.
     *
     * @param  array  $options1
     * @param  array  $options2
     */
    public function compareOptions($options1, $options2): ?bool
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        }

        if (
            isset($options1['selected_configurable_option'])
            && isset($options2['selected_configurable_option'])
        ) {
            return $options1['selected_configurable_option'] === $options2['selected_configurable_option'];
        }

        if (! isset($options1['selected_configurable_option'])) {
            return false;
        }

        if (! isset($options2['selected_configurable_option'])) {
            return false;
        }

        return null;
    }

    /**
     * Return additional information for items.
     *
     * @param  array  $data
     * @return array
     */
    public function getAdditionalOptions($data)
    {
        $childProduct = resolve(ProductRepository::class)->find($data['selected_configurable_option']);

        foreach ($this->product->super_attributes as $attribute) {
            $option = $attribute->options()->where('id', $childProduct->{$attribute->code})->first();

            $data['attributes'][$attribute->code] = [
                'attribute_name' => $attribute->name ?: $attribute->admin_name,
                'option_id'      => $option->id,
                'option_label'   => $option->label ?: $option->admin_name,
            ];
        }

        return $data;
    }
}
