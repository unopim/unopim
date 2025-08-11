<?php

namespace Webkul\MagicAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Repositories\ProductRepository;

class SaveTranslatedAllAttributesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    protected $translatedValues;

    protected $channel;

    /**
     * Create a new job instance.
     */
    public function __construct($productId, $translatedValues, $channel)
    {
        $this->productId = $productId;
        $this->translatedValues = $translatedValues;
        $this->channel = $channel;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductRepository $productRepository)
    {
        $product = $productRepository->find($this->productId);
        $data = $product->values;

        foreach ($this->translatedValues as $transData) {
            $field = $transData['field'];
            $translatedData = $transData['translations'];

            foreach ($translatedData as $values) {
                $locale = $values['locale'];
                $value = $values['content'];

                if (! isset($data['channel_locale_specific'])) {
                    $data['channel_locale_specific'] = [];
                }

                if (! isset($data['channel_locale_specific'][$this->channel])) {
                    $data['channel_locale_specific'][$this->channel] = [];
                }

                $existingData = $data['channel_locale_specific'][$this->channel][$locale] ?? [];

                if (core()->getConfigData('general.magic_ai.translation.replace')) {
                    $existingData[$field] = $value;
                } else if (! isset($existingData[$field])) {
                    $existingData[$field] = $value;
                }

                $data['channel_locale_specific'][$this->channel][$locale] = $existingData;
            }
        }

        $product->values = $data;
        $product->save();
    }
}
