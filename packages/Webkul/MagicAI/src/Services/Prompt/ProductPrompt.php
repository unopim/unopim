<?php

namespace Webkul\MagicAI\Services\Prompt;

use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Facades\ProductValueMapper as ProductValueMapperFacade;
use Webkul\Product\Repositories\ProductRepository;

class ProductPrompt extends AbstractPrompt
{
    private static ?ProductPrompt $instance = null;

    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Gets the singleton instance of AIModel.
     */
    public static function getInstance(): ProductPrompt
    {
        if (! self::$instance instanceof ProductPrompt) {
            self::$instance = new self(resolve(ProductRepository::class), resolve(AttributeRepository::class));
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Replaces placeholders in the prompt with product attribute values.
     */
    public function updatePrompt(string $prompt, int $productId): string
    {
        $product = $this->getProductById($productId);

        if (! $product) {
            return $prompt;
        }

        $attributes = $this->searchStringWithAt($prompt);
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
        return Str::matchAll('/@\w+/', $string)->toArray();
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
