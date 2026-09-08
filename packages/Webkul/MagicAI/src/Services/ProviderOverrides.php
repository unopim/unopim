<?php

namespace Webkul\MagicAI\Services;

use Illuminate\Support\Arr;

/**
 * Builds laravel/ai provider config overrides from a validated base plus
 * user-supplied extras.
 *
 * The endpoint and credential keys are stripped from the extras at merge
 * time, not only at validation time: a platform row persisted before this
 * guard existed can still carry an `extras.url` that would otherwise
 * overwrite the SafeWebhookUrl-checked api_url and reach an internal host.
 */
class ProviderOverrides
{
    /**
     * Keys the platform record owns; extras may never redefine them.
     */
    public const RESERVED_KEYS = ['url', 'key', 'api_key', 'base_url'];

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>|string|null  $extras
     * @return array<string, mixed>
     */
    public static function build(array $base, array|string|null $extras): array
    {
        $decoded = self::decode($extras);

        return $decoded === [] ? $base : array_merge($base, $decoded);
    }

    /**
     * Decode extras into a reserved-key-free array, or [] when unusable.
     *
     * @param  array<string, mixed>|string|null  $extras
     * @return array<string, mixed>
     */
    public static function decode(array|string|null $extras): array
    {
        if (is_string($extras)) {
            $extras = json_decode($extras, true);
        }

        if (! is_array($extras)) {
            return [];
        }

        return Arr::except($extras, self::RESERVED_KEYS);
    }
}
