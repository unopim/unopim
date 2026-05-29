<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;

class ThemeCustomization
{
    public mixed $themeCustomizationRepository;

    /**
     * After theme customization create
     *
     * @param  \Webkul\Shop\Contracts\ThemeCustomization  $themeCustomization
     */
    public function afterCreate(mixed $themeCustomization): void
    {
        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }

    /**
     * After theme customization update
     *
     * @param  \Webkul\Shop\Contracts\ThemeCustomization  $themeCustomization
     */
    public function afterUpdate(mixed $themeCustomization): void
    {
        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }

    /**
     * Before theme customization delete
     */
    public function beforeDelete(int $themeCustomizationId): void
    {
        $themeCustomization = $this->themeCustomizationRepository->find($themeCustomizationId);

        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }
}
