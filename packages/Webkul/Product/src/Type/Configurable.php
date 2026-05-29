<?php

namespace Webkul\Product\Type;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Admin\Validations\ConfigurableUniqueSku;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class Configurable extends AbstractType
{
    /**
     * These are the types which can be fillable when generating variant.
     */
    protected array $fillableTypes = [
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
     */
    protected bool $hasVariants = true;

    /**
     * Attribute stored bu their code.
     *
     * @var bool
     */
    protected array $attributesByCode = [];

    /**
     * Attribute stored bu their id.
     *
     * @var bool
     */
    protected array $attributesById = [];

    /**
     * Get default variant.
     */
    public function getDefaultVariant(): ?Product
    {
        return $this->product->variants()->find($this->getDefaultVariantId());
    }

    /**
     * Get default variant id.
     */
    public function getDefaultVariantId(): ?int
    {
        return $this->product->additional['default_variant_id'] ?? null;
    }

    /**
     * Set default variant id.
     */
    public function setDefaultVariantId(int $defaultVariantId): void
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
    #[\Override]
    public function create(array $data): Product
    {
        $product = $this->productRepository->getModel()->create($data);

        $product->values = [self::COMMON_VALUES_KEY => ['sku' => $product->sku]];

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
     * @return \Webkul\Product\Contracts\Product
     */
    #[\Override]
    public function update(array $data, int $id, string $attribute = 'id'): Product
    {
        $product = parent::update($data, $id, $attribute);

        $this->updateDefaultVariantId();

        if (request()->route()?->getName() == 'admin.catalog.products.mass_update') {
            return $product;
        }

        $previousVariantIds = $product->variants->pluck('id');

        $productSuperAttributes = $product->super_attributes;

        $uniqueAttributes = $this->getUniqueAttributes();

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
     * Create variant.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @param  array  $permutation
     * @return \Webkul\Product\Contracts\Product
     */
    public function createVariant(Product $product, Collection $productSuperAttributes, array $data = [], Collection|array $uniqueAttributes = []): Product
    {
        $variant = $this->productRepository->getModel()->create([
            'parent_id'           => $product->id,
            'type'                => 'simple',
            'attribute_family_id' => $product->attribute_family_id,
            'sku'                 => $data['sku'],
        ]);

        $variantValues = $this->filterUniqueAttributeValues($product->values, $uniqueAttributes);

        foreach ($productSuperAttributes as $attribute) {
            $attrCode = $attribute->code;

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
     * @return \Webkul\Product\Contracts\Product
     */
    public function updateVariant(array $data, int $id): Product
    {
        $variant = $this->productRepository->find($id);

        $variantValues = $variant->values;

        foreach ($data['super_attributes'] ?? [] as $attribute) {
            $attrCode = $attribute->code;

            $variantValues[self::COMMON_VALUES_KEY][$attrCode] = $data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY][$attrCode];
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
     */
    #[\Override]
    protected function copyRelationships(\Webkul\Product\Contracts\Product $product): void
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.skipAttributesOnCopy') ?? [];

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
     */
    #[\Override]
    public function getChildrenIds(): array
    {
        return $this->product->variants()->pluck('id')->toArray();
    }

    /**
     * Return validation rules.
     */
    #[\Override]
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
    #[\Override]
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
     */
    #[\Override]
    public function getAdditionalOptions($data): array
    {
        $childProduct = app(ProductRepository::class)->find($data['selected_configurable_option']);

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
