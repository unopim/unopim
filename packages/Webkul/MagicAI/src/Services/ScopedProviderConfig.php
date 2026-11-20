<?php

namespace Webkul\MagicAI\Services;

use Closure;
use Laravel\Ai\Ai;
use RuntimeException;
use Webkul\MagicAI\Enums\AiProvider;

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
     * restoring the original provider config afterwards. Every provider call
     * made inside it refuses redirects, and a platform base URL is pinned to
     * the address it validated to, so the call cannot be steered to an
     * internal host by a 30x hop or DNS rebinding. A base URL that cannot be
     * validated and pinned refuses the call before any config changes; the
     * local Ollama daemon default is the one URL left unpinned.
     *
     * @param  array<string, mixed>  $overrides
     *
     * @throws RuntimeException
     */
    public static function run(string $configKey, array $overrides, Closure $callback): mixed
    {
        $url = $overrides['url'] ?? null;

        $guard = app(ProviderCallGuard::class);

        $isLocalOllama = $configKey === AiProvider::Ollama->configKey() && AiProvider::Ollama->isLocalOllamaDefault(is_string($url) ? $url : null);

        $pinUrl = is_string($url) && ! $isLocalOllama ? $url : null;

        $previousGuard = $guard->enter($pinUrl);

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

            $guard->restore($previousGuard);
        }
    }
}
