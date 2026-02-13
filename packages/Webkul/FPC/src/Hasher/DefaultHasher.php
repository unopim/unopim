<?php

namespace Webkul\FPC\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\DefaultHasher as BaseDefaultHasher;

class DefaultHasher extends BaseDefaultHasher
{
    /**
     * Get the hash for the given request.
     *
     * Overrides the base hasher to include the current tenant ID in the hash,
     * ensuring tenant-isolated cache entries.
     */
    public function getHashFor(Request $request): string
    {
        $cacheNameSuffix = $this->getCacheNameSuffix($request);

        $tenantId = core()->getCurrentTenantId() ?? 'global';

        return 'responsecache-'.hash(
            'xxh128',
            "{$request->getHost()}-{$this->getNormalizedRequestUri($request)}-{$request->getMethod()}/tenant-{$tenantId}/{$cacheNameSuffix}"
        );
    }

    /**
     * Get the hash for the given request.
     */
    protected function getNormalizedRequestUri(Request $request): string
    {
        return $request->getBaseUrl().$request->getPathInfo();
    }

    /**
     * Get the cache name suffix for the given request.
     */
    protected function getCacheNameSuffix(Request $request): string
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        $cacheNameSuffix = $this->cacheProfile->useCacheNameSuffix($request);

        return $cacheNameSuffix;
    }
}
