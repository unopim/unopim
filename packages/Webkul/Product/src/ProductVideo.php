<?php

namespace Webkul\Product;

use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class ProductVideo
{
    /**
     * Retrieve collection of videos
     *
     * @param  Product  $product
     */
    public function getVideos($product): array
    {
        if (! $product) {
            return [];
        }

        $videos = [];

        foreach ($product->videos as $video) {
            if (! Storage::has($video->path)) {
                continue;
            }

            $videos[] = [
                'type'      => $video->type,
                'video_url' => Storage::url($video->path),
            ];
        }

        return $videos;
    }
}
