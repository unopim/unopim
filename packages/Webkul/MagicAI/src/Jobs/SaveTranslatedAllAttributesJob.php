<?php

namespace Webkul\MagicAI\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\ProductValueMapper;

class SaveTranslatedAllAttributesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $productId, protected $translatedValues, protected $channel) {}

    /**
     * Execute the job.
     */
    public function handle(
        ProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        ProductValueMapper $productValueMapper
    ): void {
        $product = $productRepository->find($this->productId);
        $values = $product->values ?? [];
        $replace = (bool) core()->getConfigData('general.magic_ai.translation.replace');

        foreach ($this->translatedValues as $transData) {
            $attribute = $attributeRepository->findOneByField('code', $transData['field']);

            if (! $attribute) {
                continue;
            }

            foreach ($transData['translations'] as $translation) {
                $locale = $translation['locale'];

                if (
                    ! $replace
                    && $productValueMapper->getScopedValue($values, $attribute, $this->channel, $locale) !== null
                ) {
                    continue;
                }

                $values = $productValueMapper->setScopedValue(
                    $values,
                    $attribute,
                    $this->channel,
                    $locale,
                    $translation['content']
                );
            }
        }

        $product->values = $values;
        $product->save();
    }
}
