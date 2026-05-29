<?php

namespace Webkul\Core\Listeners;

use Illuminate\Support\Facades\Log;
use Prettus\Repository\Events\RepositoryEventBase;
use Prettus\Repository\Helpers\CacheKeys;
use Prettus\Repository\Listeners\CleanCacheRepository as BaseCleanCacheRepository;

class CleanCacheRepository extends BaseCleanCacheRepository
{
    #[\Override]
    public function handle(RepositoryEventBase $event): void
    {
        try {
            $this->repository = $event->getRepository();

            $cleanEnabled = $this->repository->allowedClean();

            if ($cleanEnabled) {
                $this->model = $event->getModel();
                $this->action = $event->getAction();

                $className = $this->repository::class;

                if (config("repository.cache.repositories.{$className}.clean.on.{$this->action}", config("repository.cache.clean.on.{$this->action}", true))) {
                    $cacheKeys = CacheKeys::getKeys($className);

                    if (is_array($cacheKeys)) {
                        foreach ($cacheKeys as $key) {
                            $this->cache->forget($key);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
