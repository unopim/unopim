<?php

namespace Webkul\MagicAI\Services;

use Closure;
use Laravel\Ai\Ai;

/**
 * Applies per-request laravel/ai provider overrides for the duration of a
 * single AI call, then restores the original config.
 *
 * Required for Octane safety: config() mutations persist across requests in
 * a long-lived worker, and AiManager caches resolved provider instances per
 * name — so the instance must be evicted both before and after the call.
 *
 * Safe for the process-per-request Octane model. NOT safe for concurrent
 * flows inside one worker (Swoole coroutines, Octane::concurrently) — the
 * config repository is process-global, so parallel AI calls with different
 * platforms in the same worker could cross-contaminate credentials. Do not
 * fan out Magic AI calls with intra-worker concurrency primitives.
 */
class ScopedProviderConfig
{
    /**
     * Run the callback with the given provider config overrides applied,
     * restoring the original provider config afterwards.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function run(string $configKey, array $overrides, Closure $callback): mixed
    {
        $prefix = "ai.providers.{$configKey}";
        $original = config($prefix);

        foreach ($overrides as $key => $value) {
            config(["{$prefix}.{$key}" => $value]);
        }

        Ai::forgetInstance($configKey);

        try {
            return $callback();
        } finally {
            config([$prefix => $original]);

            Ai::forgetInstance($configKey);
        }
    }
}
