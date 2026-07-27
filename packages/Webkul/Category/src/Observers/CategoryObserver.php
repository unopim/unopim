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
    public function deleted($category): void
    {
        Storage::deleteDirectory('category/'.$category->id);
    }

    /**
     * Handle the Category "saved" event.
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     */
    public function saved($category): void
    {
        // Bump direct children in one query. touch() would re-fire this observer
        // per child and recurse the whole subtree — an O(descendants) write storm.
        $category->children()->getQuery()->update(['updated_at' => now()]);
    }
}
