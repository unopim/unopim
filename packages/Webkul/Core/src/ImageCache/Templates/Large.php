<?php

namespace Webkul\Core\ImageCache\Templates;

use Webkul\Core\ImageCache\ImageCache;

class Large
{
    /**
     * Apply the filter to the image cache pipeline.
     */
    public function applyFilter(ImageCache $image): ImageCache
    {
        return $image->cover(600, 600);
    }
}
