<?php

namespace Webkul\Category\Observers;

use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category;

class CategoryObserver
{
    /**
     * Handle the Category "deleted" event.
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     */
    public function deleted(Category $category): void
    {
        Storage::deleteDirectory('category/'.$category->id);
    }

    /**
     * Handle the Category "saved" event.
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     */
    public function saved(Category $category): void
    {
        foreach ($category->children as $child) {
            $child->touch();
        }
    }
}
