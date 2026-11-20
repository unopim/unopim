<?php

namespace Webkul\MagicAI\Services;

use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Webkul\Webhook\Validators\SafeWebhookUrl;

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
     * made inside it refuses redirects, and a platform base URL that validates
     * as public is pinned to the address it resolved to, so the call cannot be
     * steered to an internal host by a 30x hop or DNS rebinding.
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
            return self::withGuardedHttp($overrides['url'] ?? null, $callback);
        } finally {
            config([$prefix => $original]);

            Ai::forgetInstance($configKey);
        }
    }

    /**
     * Run the callback with the guarded options on every Http client request,
     * restoring the previous global options afterwards.
     */
    protected static function withGuardedHttp(mixed $url, Closure $callback): mixed
    {
        /** @var Factory $factory */
        $factory = Http::getFacadeRoot();

        $previous = (fn (): mixed => $this->globalOptions)->call($factory);

        $guarded = is_string($url) && $url !== ''
            ? SafeWebhookUrl::httpOptions($url)
            : ['allow_redirects' => false];

        $factory->globalOptions(array_replace((array) value($previous), $guarded));

        try {
            return $callback();
        } finally {
            $factory->globalOptions($previous);
        }
    }
}
