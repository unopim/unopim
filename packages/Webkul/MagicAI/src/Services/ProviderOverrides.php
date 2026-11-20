<?php

namespace Webkul\MagicAI\Services;

/**
 * Builds laravel/ai provider config overrides from a validated base plus
 * user-supplied extras.
 *
 * Extras are filtered to an allowlist at merge time, not only at validation
 * time: a platform row persisted before this guard existed can still carry a
 * `url`, `driver`, `region` or `headers` key that would swap the provider
 * class or move the request off the SafeWebhookUrl-checked api_url.
 */
class ProviderOverrides
{
    /**
     * Keys the platform record owns; extras may never redefine them. Matched
     * case-insensitively, so a legacy `URL` is stripped alongside `url`.
     */
    public const RESERVED_KEYS = ['url', 'key', 'api_key', 'base_url'];

    /**
     * The only extras a platform may set: names and versions that select a
     * deployment or feature on the platform's own host, never a host, driver,
     * region or header.
     */
    public const ALLOWED_KEYS = [
        'deployment',
        'embedding_deployment',
        'image_deployment',
        'api_version',
        'organization',
        'project',
        'anthropic_beta',
    ];

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
     * Decode extras into an array of allowlisted keys, or [] when unusable.
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

        return array_filter(
            $extras,
            fn (string|int $key): bool => in_array($key, self::ALLOWED_KEYS, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
