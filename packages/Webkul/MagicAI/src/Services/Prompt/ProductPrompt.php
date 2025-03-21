<?php

namespace Webkul\MagicAI\Services\Prompt;

use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Facades\ProductValueMapper as ProductValueMapperFacade;
use Webkul\Product\Repositories\ProductRepository;

class ProductPrompt extends AbstractPrompt
{
    private static $instance;

    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Gets the singleton instance of AIModel.
     */
    public static function getInstance(): ProductPrompt
    {
        if (self::$instance === null) {
            self::$instance = new self(app(ProductRepository::class), app(AttributeRepository::class));
        }

        return self::$instance;
    }

    /**
     * Replaces placeholders in the prompt with product attribute values.
     */
    public function updatePrompt(string $prompt, int $productId): string
    {
        $attributes = $this->searchStringWithAt($prompt);
        $product = $this->getProductById($productId);
        $productData = $product->toArray();
        $locale = core()->getRequestedLocaleCode();
        $channel = core()->getRequestedChannelCode();

        $values = array_merge(
            ProductValueMapperFacade::getChannelLocaleSpecificFields($productData, $channel, $locale),
            ProductValueMapperFacade::getLocaleSpecificFields($productData, $locale),
            ProductValueMapperFacade::getChannelSpecificFields($productData, $channel),
            ProductValueMapperFacade::getCommonFields($productData)
        );

        foreach ($attributes as $attributeCodeWithAt) {
            $attributeCode = Str::replaceFirst('@', '', $attributeCodeWithAt);
            $attribute = $this->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            $value = $this->getValue($values, $attributeCode);
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
}
