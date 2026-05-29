<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Contracts\Category as CategoryContract;
use Webkul\Category\Repositories\CategoryRepository;

class Category
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

    /**
     * After category update
     */
    public function afterUpdate(CategoryContract $category): void
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
     */
    public function beforeDelete(int $categoryId): void
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
