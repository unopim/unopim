<?php

namespace Webkul\MagicAI\Services;

use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Support\ModelRecommender;

/**
 * The platform the hosting owner provisions from the CLI. Its stored key stays
 * pinned to its provider, endpoint and model list wherever it is used; any
 * other platform, or a managed one given the client's own key, is unrestricted.
 */
class ManagedPlatform
{
    /**
     * Whether the managed provider and model list are configured.
     */
    public function isConfigured(): bool
    {
        return AiProvider::tryFrom($this->provider()) instanceof AiProvider && $this->models() !== [];
    }

    /**
     * Whether the platform record is the managed one.
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
        return $apiKey === null || $apiKey === '' || preg_match('/^\*+$/', $apiKey) === 1;
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

    /**
     * The only models the managed platform may use.
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
     * Validation errors, keyed by field, for a submission that would send the
     * managed platform's stored key outside its provider, endpoint, extras or
     * model list. Empty for any other platform or when a new key is supplied.
     *
     * @param  string[]  $models
     * @param  array<string, mixed>  $extras
     * @return array<string, string>
     */
    public function violations(?MagicAIPlatform $platform, bool $usesStoredKey, ?string $provider, ?string $apiUrl, array $models, array $extras = []): array
    {
        if (! $usesStoredKey || ! $this->isManagedPlatform($platform)) {
            return [];
        }

        $errors = [];

        $apiUrl = rtrim(trim((string) $apiUrl), '/');

        $apiUrl = $apiUrl === '' ? $this->fallbackUrl($provider) : $apiUrl;

        if ($provider !== $this->provider() || $apiUrl !== $this->apiUrl() || $extras !== []) {
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
     * The endpoint a platform saved without a URL is called on, which is the
     * provider's default.
     */
    protected function fallbackUrl(?string $provider): string
    {
        return rtrim((string) AiProvider::tryFrom((string) $provider)?->defaultUrl(), '/');
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
