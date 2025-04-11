<?php

namespace Webkul\Product\Type;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\Admin\Validations\ConfigurableUniqueSku;
use Webkul\Product\Facades\ProductImage;

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
     * Is a composite product type.
     *
     * @var bool
     */
    protected $isComposite = true;

    /**
     * Show quantity box.
     *
     * @var bool
     */
    protected $showQuantityBox = true;

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
     * @return \Webkul\Product\Models\Product
     */
    public function getDefaultVariant()
    {
        return $this->product->variants()->find($this->getDefaultVariantId());
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
     * @return void
     */
    public function setDefaultVariantId($defaultVariantId)
    {
        $this->product->additional = array_merge($this->product->additional ?? [], [
            'default_variant_id' => $defaultVariantId,
        ]);
    }

    /**
     * Update default variant id if present in request.
     *
     * @return void
     */
    public function updateDefaultVariantId()
    {
        if (! $defaultVariantId = request()->get('default_variant_id')) {
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

        $previousVariantIds = $product->variants->pluck('id');

        $productSuperAttributes = $product->super_attributes;

        $uniqueAttributes = $this->getUniqueAttributes();

        if (isset($data['variants'])) {
            foreach ($data['variants'] as $variantId => $variantData) {
                if (Str::contains($variantId, 'variant_')) {
                    $this->createVariant($product, $productSuperAttributes, $variantData, $uniqueAttributes);
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
     * @param  array  $data
     * @return \Webkul\Product\Contracts\Product
     */
    public function createVariant($product, $productSuperAttributes, $data = [], Collection|array $uniqueAttributes = [])
    {
        $variant = $this->productRepository->getModel()->create([
            'parent_id'           => $product->id,
            'type'                => 'simple',
            'attribute_family_id' => $product->attribute_family_id,
            'sku'                 => $data['sku'],
        ]);

        $variantValues = $product->values;

        foreach ($productSuperAttributes as $attribute) {
            $attrCode = $attribute->code;

            $variantValues[self::COMMON_VALUES_KEY][$attrCode] = $data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY][$attrCode];
        }

        foreach ($uniqueAttributes as $unique) {
            $uniqueValue = $unique->getValueFromProductValues($variantValues, core()->getRequestedChannelCode(), core()->getRequestedLocaleCode());

            if (empty($uniqueValue)) {
                continue;
            }

            $unique->setProductValue('', $variantValues, core()->getRequestedChannelCode(), core()->getRequestedLocaleCode());
        }

        $variantValues[self::COMMON_VALUES_KEY]['sku'] = $data['sku'];

        $variant->values = $variantValues;

        $variant->save();

        return $variant;
    }

    /**
     * @param  mixed  $attribute
     * @param  mixed  $value
     * @return array
     */
    public function getAttributeTypeValues($attribute, $value)
    {
        $attributeTypeFields = array_fill_keys(array_values($attribute->attributeTypeFields), null);

        $attributeTypeFields[$attribute->column_name] = $value;

        return $attributeTypeFields;
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
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */
    protected function copyRelationships($product)
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
     *
     * @return array
     */
    public function getChildrenIds()
    {
        return $this->product->variants()->pluck('id')->toArray();
    }

    /**
     * Return validation rules.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return [
            'variants.*.sku'    => [
                'required',
                new ConfigurableUniqueSku($this->getChildrenIds()),
            ],
        ];
    }

    /**
     * Return true if item can be moved to cart from wishlist.
     *
     * @param  \Webkul\Customer\Contracts\Wishlist  $item
     * @return bool
     */
    public function canBeMovedFromWishlistToCart($item)
    {
        return isset($item->additional['selected_configurable_option']);
    }

    /**
     * Compare options.
     *
     * @param  array  $options1
     * @param  array  $options2
     * @return bool
     */
    public function compareOptions($options1, $options2)
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
    }

    /**
     * Return additional information for items.
     *
     * @param  array  $data
     * @return array
     */
    public function getAdditionalOptions($data)
    {
        $childProduct = app('Webkul\Product\Repositories\ProductRepository')->find($data['selected_configurable_option']);

        foreach ($this->product->super_attributes as $attribute) {
            $option = $attribute->options()->where('id', $childProduct->{$attribute->code})->first();

            $data['attributes'][$attribute->code] = [
                'attribute_name' => $attribute->name ? $attribute->name : $attribute->admin_name,
                'option_id'      => $option->id,
                'option_label'   => $option->label ? $option->label : $option->admin_name,
            ];
        }

        return $data;
    }

    /**
     * Get actual ordered item.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem  $item
     * @return \Webkul\Checkout\Contracts\CartItem|\Webkul\Sales\Contracts\OrderItem|\Webkul\Sales\Contracts\InvoiceItem|\Webkul\Sales\Contracts\ShipmentItem|\Webkul\Customer\Contracts\Wishlist
     */
    public function getOrderedItem($item)
    {
        return $item->child;
    }

    /**
     * Get product base image.
     *
     * @param  \Webkul\Customer\Contracts\Wishlist|\Webkul\Checkout\Contracts\CartItem  $item
     * @return array
     */
    public function getBaseImage($item)
    {
        $product = $item->product;

        if ($item instanceof \Webkul\Customer\Contracts\Wishlist) {
            if (isset($item->additional['selected_configurable_option'])) {
                $product = $this->productRepository->find($item->additional['selected_configurable_option']);
            }
        } else {
            if (count($item->child->product->images)) {
                $product = $item->child->product;
            }
        }

        return ProductImage::getProductBaseImage($product);
    }
}
