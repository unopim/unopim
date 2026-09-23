<?php

namespace Webkul\MagicAI\Services;

use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Support\ModelRecommender;

/**
 * The platform account the installation ships with. Its key is recognised on
 * every request, so it stays pinned to its provider, endpoint and model list
 * wherever it is used; any other key or provider is left unrestricted.
 */
class ManagedPlatform
{
    /**
     * Whether a managed account is configured.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && AiProvider::tryFrom($this->provider()) instanceof AiProvider;
    }

    /**
     * Whether the given API key is the managed account's key.
     */
    public function isManagedKey(?string $apiKey): bool
    {
        return $this->isConfigured()
            && $apiKey !== null
            && $apiKey !== ''
            && hash_equals($this->apiKey(), $apiKey);
    }

    /**
     * Whether the platform record runs on the managed account's key.
     */
    public function isManagedPlatform(MagicAIPlatform $platform): bool
    {
        return $this->isManagedKey($platform->safeApiKey());
    }

    public function provider(): string
    {
        return (string) config('magic_ai.managed.provider');
    }

    public function label(): string
    {
        return (string) (config('magic_ai.managed.label') ?: AiProvider::from($this->provider())->label());
    }

    public function apiUrl(): string
    {
        return rtrim((string) (config('magic_ai.managed.api_url') ?: AiProvider::from($this->provider())->defaultUrl()), '/');
    }

    public function apiKey(): string
    {
        return (string) config('magic_ai.managed.api_key');
    }

    /**
     * The only models the managed account may use.
     *
     * @return string[]
     */
    public function models(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($model): string => trim((string) $model),
            (array) config('magic_ai.managed.models', [])
        ))));
    }

    /**
     * Validation errors, keyed by field, for a platform submission that uses
     * the managed key outside its provider, endpoint, extras or model list.
     * Empty when the key is not the managed one.
     *
     * @param  string[]  $models
     * @param  array<string, mixed>  $extras
     * @return array<string, string>
     */
    public function violations(?string $apiKey, ?string $provider, ?string $apiUrl, array $models, array $extras = []): array
    {
        if (! $this->isManagedKey($apiKey)) {
            return [];
        }

        $errors = [];

        $apiUrl = rtrim(trim((string) $apiUrl), '/');

        if ($provider !== $this->provider() || ($apiUrl !== '' && $apiUrl !== $this->apiUrl()) || $extras !== []) {
            $errors['api_key'] = trans('admin::app.configuration.platform.message.managed-connection-locked');
        }

        if (array_diff($models, $this->models()) !== []) {
            $errors['models'] = trans('admin::app.configuration.platform.message.managed-models-only', [
                'allowed' => implode(', ', $this->models()),
            ]);
        }

        return $errors;
    }

    /**
     * The model to send on the platform's credentials. An unmanaged platform
     * keeps the requested model; a managed one swaps a model outside its list
     * for one on it, so a stale selection never reaches the managed account.
     */
    public function resolveModel(MagicAIPlatform $platform, ?string $model): ?string
    {
        if (! $this->isManagedPlatform($platform) || in_array($model, $this->models(), true)) {
            return $model;
        }

        return ModelRecommender::pickTextModel($this->models());
    }
}
