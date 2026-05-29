<?php

namespace Webkul\Product\Helpers;

use Illuminate\Support\Collection;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Facades\ProductVideo;

class ConfigurableOption
{
    /**
     * Allowed Products.
     *
     * @return array
     */
    protected array $allowedVariants = [];

    /**
     * Super Attributes
     *
     * @return array
     */
    protected array $superAttributes = [];

    /**
     * Returns the allowed variants.
     */
    public function getAllowedVariants(Product $product): array
    {
        if (count($this->allowedVariants) > 0) {
            return $this->allowedVariants;
        }

        $variantCollection = $product->variants()
            ->with([
                'parent',
                'attribute_values',
                'price_indices',
                'inventory_indices',
                'images',
                'videos',
            ])
            ->get();

        foreach ($variantCollection as $variant) {
            if ($variant->isSaleable()) {
                $this->allowedVariants[] = $variant;
            }
        }

        return $this->allowedVariants;
    }

    /**
     * Returns the allowed variants JSON.
     *
     * @param  \Webkul\Product\Models\Product  $product
     */
    public function getConfigurationConfig(Product $product): array
    {
        $options = $this->getOptions($product, $this->getAllowedVariants($product));

        $config = [
            'attributes'     => $this->getAttributesData($product, $options),
            'index'          => $options['index'] ?? [],
            'variant_prices' => $this->getVariantPrices($product),
            'variant_images' => $this->getVariantImages($product),
            'variant_videos' => $this->getVariantVideos($product),
        ];

        return array_merge($config, $product->getTypeInstance()->getProductPrices());
    }

    /**
     * Get allowed attributes.
     */
    public function getAllowAttributes(Product $product): Collection
    {
        return $this->superAttributes[$product->id] ?? $this->superAttributes[$product->id] = $product->super_attributes()
            ->with(['translations', 'options', 'options.translations'])
            ->get();
    }

    /**
     * Get configurable product options.
     */
    public function getOptions(Product $currentProduct, array $allowedProducts): array
    {
        $options = [];

        $allowAttributes = $this->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            foreach ($allowAttributes as $productAttribute) {
                $productAttributeId = $productAttribute->id;

                $attributeValue = $product->{$productAttribute->code};

                $options[$productAttributeId][$attributeValue][] = $product->id;

                $options['index'][$product->id][$productAttributeId] = $attributeValue;
            }
        }

        return $options;
    }

    /**
     * Get product attributes.
     */
    public function getAttributesData(Product $product, array $options = []): array
    {
        $attributes = [];

        $allowAttributes = $this->getAllowAttributes($product);

        foreach ($allowAttributes as $attribute) {
            $attributes[] = [
                'id'          => $attribute->id,
                'code'        => $attribute->code,
                'label'       => $attribute->name ?: $attribute->admin_name,
                'swatch_type' => $attribute->swatch_type,
                'options'     => $this->getAttributeOptionsData($attribute, $options),
            ];
        }

        return $attributes;
    }

    /**
     * Get attribute options data.
     */
    protected function getAttributeOptionsData(Attribute $attribute, array $options): array
    {
        $attributeOptionsData = [];

        foreach ($attribute->options->sortBy('sort_order') as $attributeOption) {
            $optionId = $attributeOption->id;

            if (! isset($options[$attribute->id][$optionId])) {
                continue;
            }

            $attributeOptionsData[] = [
                'id'           => $optionId,
                'label'        => $attributeOption->label ?: $attributeOption->admin_name,
                'swatch_value' => $attribute->swatch_type == 'image' ? $attributeOption->swatch_value_url : $attributeOption->swatch_value,
                'products'     => $options[$attribute->id][$optionId],
            ];
        }

        return $attributeOptionsData;
    }

    /**
     * Get product prices for configurable variations.
     */
    protected function getVariantPrices(Product $product): array
    {
        $prices = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $prices[$variant->id] = $variant->getTypeInstance()->getProductPrices();
        }

        return $prices;
    }

    /**
     * Get product images for configurable variations.
     */
    protected function getVariantImages(Product $product): array
    {
        $images = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $images[$variant->id] = ProductImage::getGalleryImages($variant);
        }

        return $images;
    }

    /**
     * Get product videos for configurable variations.
     */
    protected function getVariantVideos(Product $product): array
    {
        $videos = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $videos[$variant->id] = ProductVideo::getVideos($variant);
        }

        return $videos;
    }
}
