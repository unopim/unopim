<?php

namespace Webkul\MagicAI\Services;

use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Support\ModelRecommender;

/**
 * A platform the hosting owner provisions from the CLI. While it runs on its
 * stored key it stays pinned to its own saved provider, endpoint and model
 * list; any other platform, or a managed one given the client's own key, is
 * unrestricted.
 */
class ManagedPlatform
{
    /**
     * Whether the platform record is a managed one.
     */
    public function isManagedPlatform(?MagicAIPlatform $platform): bool
    {
        return (bool) $platform?->is_managed;
    }

    /**
     * Whether a submitted key stands for the stored one, as a masked or
     * omitted key does.
     */
    public function usesStoredKey(?string $apiKey): bool
    {
        return $apiKey === null || $apiKey === '' || $apiKey === '0' || preg_match('/^\*+$/', $apiKey) === 1;
    }

    /**
     * The endpoint the platform is called on; an empty URL means the
     * provider's default.
     */
    public function apiUrl(MagicAIPlatform $platform): string
    {
        return $this->effectiveUrl($platform->provider, $platform->api_url);
    }

    /**
     * The models the platform is saved with.
     *
     * @return string[]
     */
    public function models(MagicAIPlatform $platform): array
    {
        return array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', (string) $platform->models))
        )));
    }

    /**
     * Validation errors, keyed by field, for a submission that would send a
     * managed platform's stored key outside its saved provider, endpoint,
     * extras or model list. Empty for any other platform or when a new key
     * is supplied.
     *
     * @param  string[]  $models
     * @param  array<string, mixed>  $extras
     * @return array<string, string>
     */
    public function violations(?MagicAIPlatform $platform, bool $usesStoredKey, ?string $provider, ?string $apiUrl, array $models, array $extras = []): array
    {
        if (! $usesStoredKey || ! $platform instanceof MagicAIPlatform || ! $this->isManagedPlatform($platform)) {
            return [];
        }

        $errors = [];

        if (
            $provider !== $platform->provider
            || $this->effectiveUrl($provider, $apiUrl) !== $this->apiUrl($platform)
            || $extras !== []
        ) {
            $errors['api_key'] = trans('admin::app.configuration.platform.message.managed-connection-locked');
        }

        $allowed = $this->models($platform);

        if (array_diff($models, $allowed) !== []) {
            $errors['models'] = trans('admin::app.configuration.platform.message.managed-models-only', [
                'allowed' => implode(', ', $allowed),
            ]);
        }

        return $errors;
    }

    /**
     * Whether an admin update would change a managed platform beyond its
     * status and default flag, which only the server command line may do.
     *
     * @param  array<string, mixed>  $data
     */
    public function changesLockedFields(MagicAIPlatform $platform, array $data): bool
    {
        if (! $this->isManagedPlatform($platform)) {
            return false;
        }

        $models = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', (string) ($data['models'] ?? '')))
        )));

        return array_key_exists('api_key', $data)
            || (string) ($data['label'] ?? '') !== (string) $platform->label
            || ($data['provider'] ?? null) !== $platform->provider
            || $this->effectiveUrl($data['provider'] ?? null, $data['api_url'] ?? null) !== $this->apiUrl($platform)
            || $models !== $this->models($platform)
            || (array_key_exists('extras', $data) && ($data['extras'] ?: []) !== ($platform->extras ?: []));
    }

    /**
     * The model to send on the platform's credentials. An unmanaged platform
     * keeps the requested model; a managed one swaps a model outside its
     * saved list for one on it, so a stale selection never reaches the
     * managed account.
     */
    public function resolveModel(MagicAIPlatform $platform, ?string $model): ?string
    {
        if (! $this->isManagedPlatform($platform) || in_array($model, $this->models($platform), true)) {
            return $model;
        }

        return ModelRecommender::pickTextModel($this->models($platform));
    }

    protected function effectiveUrl(?string $provider, ?string $apiUrl): string
    {
        $apiUrl = rtrim(trim((string) $apiUrl), '/');

        return $apiUrl !== ''
            ? $apiUrl
            : rtrim((string) AiProvider::tryFrom((string) $provider)?->defaultUrl(), '/');
    }
}
