<?php

namespace Webkul\AdminApi\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Versioned response cache for the slow-changing structure entities served
 * by the REST API (attributes, families, channels, locales, ...).
 *
 * Writes never delete response keys: model events bump the group's version,
 * which rotates every response key built from it; superseded keys simply
 * expire by TTL. This works on any cache store (no tag support required)
 * and stays Octane-safe (no state beyond the shared cache).
 */
class StructureCache
{
    public const VERSION_KEY_PREFIX = 'api:structure:ver:';

    public const RESPONSE_KEY_PREFIX = 'api:structure:resp:';

    /**
     * Every cacheable structure group; bumpAll() rotates each of them.
     *
     * @var array<int, string>
     */
    public const GROUPS = [
        'attributes',
        'attribute_groups',
        'families',
        'category_fields',
        'channels',
        'locales',
        'currencies',
    ];

    public function enabled(): bool
    {
        return (bool) config('api.structure_cache.enabled', true);
    }

    public function ttl(): int
    {
        return (int) config('api.structure_cache.ttl', 3600);
    }

    public function version(string $group): int
    {
        return (int) Cache::get(self::VERSION_KEY_PREFIX.$group, 1);
    }

    public function bump(string ...$groups): void
    {
        foreach ($groups as $group) {
            Cache::increment(self::VERSION_KEY_PREFIX.$group);
        }
    }

    public function bumpAll(): void
    {
        $this->bump(...self::GROUPS);
    }

    public function remember(string $group, string $keySuffix, \Closure $callback): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        return Cache::remember(
            self::RESPONSE_KEY_PREFIX.$group.':v'.$this->version($group).':'.$keySuffix,
            $this->ttl(),
            $callback
        );
    }
}
