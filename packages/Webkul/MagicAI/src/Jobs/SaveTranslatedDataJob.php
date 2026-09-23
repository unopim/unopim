<?php

namespace Webkul\MagicAI\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\ProductValueMapper;

class SaveTranslatedDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $productId, protected $translatedData, protected $channel, protected $field) {}

    /**
     * Execute the job.
     */
    public function handle(
        ProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        ProductValueMapper $productValueMapper
    ): void {
        $attribute = $attributeRepository->findOneByField('code', $this->field);

        if (! $attribute) {
            return;
        }

        $product = $productRepository->find($this->productId);
        $values = $product->values ?? [];
        $replace = (bool) core()->getConfigData('general.magic_ai.translation.replace');

        foreach ($this->translatedData as $transData) {
            $locale = $transData['locale'];

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
                $transData['content']
            );
        }

        $product->values = $values;
        $product->save();
    }
}
