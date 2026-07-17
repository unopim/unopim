<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Repositories\CategoryRepository;

class Category
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

    /**
     * After category update
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     */
    public function afterUpdate($category): void
    {
        foreach (core()->getAllActiveLocales() as $locale) {
            if ($categoryTranslation = $category->translate($locale->code)) {
                ResponseCache::forget($categoryTranslation->name);
            }

            ResponseCache::forget($category->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->name);
        }
    }

    /**
     * Before category delete
     *
     * @param  int  $categoryId
     */
    public function beforeDelete($categoryId): void
    {
        $category = $this->categoryRepository->find($categoryId);

        foreach (core()->getAllActiveLocales() as $locale) {
            if ($categoryTranslation = $category->translate($locale->code)) {
                ResponseCache::forget($categoryTranslation->name);
            }

            ResponseCache::forget($category->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->name);
        }
    }
}
