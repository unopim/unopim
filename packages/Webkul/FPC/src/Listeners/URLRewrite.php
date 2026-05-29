<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class URLRewrite
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected URLRewriteRepository $urlRewriteRepository) {}

    /**
     * After URL Rewrite update
     *
     * @param  \Webkul\Marketing\Contracts\URLRewrite  $urlRewrite
     */
    public function afterUpdate(mixed $urlRewrite): void
    {
        ResponseCache::forget('/'.$urlRewrite->request_path);
    }

    /**
     * Before URL Rewrite delete
     */
    public function beforeDelete(int $urlRewriteId): void
    {
        $urlRewrite = $this->urlRewriteRepository->find($urlRewriteId);

        ResponseCache::forget('/'.$urlRewrite->request_path);
    }
}
