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
    public function afterUpdate(mixed $page): void
    {
        ResponseCache::forget('/page/'.$page->url_key);
    }

    /**
     * Before page delete
     */
    public function beforeDelete(int $pageId): void
    {
        $page = $this->pageRepository->find($pageId);

        ResponseCache::forget('/page/'.$page->url_key);
    }
}
