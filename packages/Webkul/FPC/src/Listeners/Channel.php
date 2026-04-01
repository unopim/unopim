<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Contracts\Category;

class Channel
{
    /**
     * After category update
     *
     * @param  Category  $category
     * @return void
     */
    public function afterUpdate($category)
    {
        ResponseCache::clear();
    }
}
