<?php

namespace Webkul\MagicAI\Services;

use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;

class Product
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    public function getPromptWithProductValues($prompt, $productId)
    {
        $attributes = $this->searchStringWithAt($prompt);
        $product = $this->getProductById($productId);
        $productData = $product->toArray();
        $locale = core()->getRequestedLocaleCode();

        foreach ($attributes as $attributeCodeWithAt) {
            $attributeCode = Str::replaceFirst('@', '', $attributeCodeWithAt);
            $attribute = $this->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            if (
                $attribute->value_per_locale
                && $attribute->value_per_channel
            ) {
                $values = $this->getChannelLocaleSpecificFields($productData, $locale, $locale);
            } elseif ($attribute->value_per_locale) {
                $values = $this->getLocaleSpecificFields($productData, $locale);
            } elseif ($attribute->value_per_channel) {
                $values = $this->getChannelSpecificFields($productData, $locale);
            } else {
                $values = $this->getCommonFields($productData);
            }

            $value = $this->getAttributeValue($values, $attributeCode);
            $prompt = Str::replaceFirst($attributeCodeWithAt, $value, $prompt);
        }

        return $prompt;
    }

    public function searchStringWithAt($string)
    {
        $matches = Str::matchAll('/@\w+/', $string)->toArray();

        return $matches;
    }

    public function getProductById($productId)
    {
        return $this->productRepository->find($productId);
    }

    public function findAttributeByCode($code)
    {
        return $this->attributeRepository->findOneByField('code', $code);
    }

    /**
     * Retrieves and formats the common fields for a product.
     *
     *
     * @return array
     */
    protected function getCommonFields(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('common', $data['values'])
        ) {
            return [];
        }

        return $data['values']['common'];
    }

    /**
     * Retrieves and formats the locale-specific fields for a product.
     *
     * @param  string  $channel
     * @return array
     */
    protected function getLocaleSpecificFields(array $data, $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['locale_specific'][$locale] ?? [];
    }

    /**
     * Retrieves and formats the channel-specific fields for a product.
     *
     * @param  string  $channel
     * @return array
     */
    protected function getChannelSpecificFields(array $data, $channel)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_specific'][$channel] ?? [];
    }

    /**
     * Retrieves and formats the channel-locale-specific fields for a product.
     *
     *
     * @return array
     */
    protected function getChannelLocaleSpecificFields(array $data, string $channel, string $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_locale_specific'][$channel][$locale] ?? [];
    }

    public function getAttributeValue(array $values, string $attributeCode)
    {
        return $values[$attributeCode] ?? '';
    }
}
