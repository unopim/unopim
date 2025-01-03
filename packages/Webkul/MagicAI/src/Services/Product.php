<?php

namespace Webkul\MagicAI\Services;

use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Facades\ProductValueMapper as ProductValueMapperFacade;

class Product
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Replaces placeholders in the prompt with product attribute values.
     */
    public function getPromptWithProductValues(string $prompt, int $productId): string
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
                $values = ProductValueMapperFacade::getChannelLocaleSpecificFields($productData, $locale, $locale);
            } elseif ($attribute->value_per_locale) {
                $values = ProductValueMapperFacade::getLocaleSpecificFields($productData, $locale);
            } elseif ($attribute->value_per_channel) {
                $values = ProductValueMapperFacade::getChannelSpecificFields($productData, $locale);
            } else {
                $values = ProductValueMapperFacade::getCommonFields($productData);
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

    public function getAttributeValue(array $values, string $attributeCode)
    {
        return $values[$attributeCode] ?? '';
    }
}
