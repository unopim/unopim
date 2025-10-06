<?php

namespace Webkul\MagicAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Repositories\ProductRepository;

class SaveTranslatedDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    protected $translatedData;

    protected $channel;

    protected $field;

    /**
     * Create a new job instance.
     */
    public function __construct($productId, $translatedData, $channel, $field)
    {
        $this->productId = $productId;
        $this->translatedData = $translatedData;
        $this->channel = $channel;
        $this->field = $field;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductRepository $productRepository)
    {
        $product = $productRepository->find($this->productId);
        $data = $product->values;

        foreach ($this->translatedData as $transData) {
            $locale = $transData['locale'];
            $value = $transData['content'];

            if (! isset($data['channel_locale_specific'])) {
                $data['channel_locale_specific'] = [];
            }

            if (! isset($data['channel_locale_specific'][$this->channel])) {
                $data['channel_locale_specific'][$this->channel] = [];
            }

            $existingData = $data['channel_locale_specific'][$this->channel][$locale] ?? [];

            if (core()->getConfigData('general.magic_ai.translation.replace') == 1) {
                $existingData[$this->field] = $value;
            } else {
                if (! isset($existingData[$this->field])) {
                    $existingData[$this->field] = $value;
                }
            }

            $data['channel_locale_specific'][$this->channel][$locale] = $existingData;
        }

        $product->values = $data;
        $product->save();
    }
}
