<?php

declare(strict_types=1);

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Contracts\Category;

class Channel
{
    /**
     * After category update
     */
    public function afterUpdate(Category $category): void
    {
        ResponseCache::clear();
    }
}
