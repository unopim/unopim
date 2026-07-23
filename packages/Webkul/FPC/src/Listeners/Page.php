<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\CMS\Repositories\PageRepository;

class Page
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected PageRepository $pageRepository) {}

    /**
     * After page update
     *
     * @param  \Webkul\CMS\Contracts\Page  $page
     */
    public function afterUpdate($page): void
    {
        ResponseCache::forget('/page/'.$page->url_key);
    }

    /**
     * Before page delete
     *
     * @param  int  $pageId
     */
    public function beforeDelete($pageId): void
    {
        $page = $this->pageRepository->find($pageId);

        ResponseCache::forget('/page/'.$page->url_key);
    }
}
