<?php

namespace Webkul\AiAgent\Chat;

/**
 * Pick the image model the generate and edit image tools send to a provider.
 *
 * The model lists live in `ai-agent.image_models`, so a provider retiring a
 * model is handled by config instead of a code change.
 */
class ImageModelSelector
{
    /**
     * Select a current image model for the provider, or the chat model when the provider has no image models.
     */
    public static function select(string $provider, string $chatModel, array $platformModels): string
    {
        $settings = self::providerSettings($provider);

        if ($settings === null) {
            return $chatModel;
        }

        if (self::isUsableImageModel($provider, $chatModel)) {
            return $chatModel;
        }

        $platformModels = array_map(strval(...), $platformModels);

        foreach ($settings['preferred'] ?? [] as $preferred) {
            if (in_array($preferred, $platformModels, true) && ! self::isRetired($preferred)) {
                return $preferred;
            }
        }

        foreach ($platformModels as $model) {
            if (self::isUsableImageModel($provider, $model)) {
                return $model;
            }
        }

        return (string) $settings['fallback'];
    }

    /**
     * Determine whether the model is a current image model of the provider.
     */
    public static function isUsableImageModel(string $provider, string $model): bool
    {
        if ($model === '' || self::isRetired($model)) {
            return false;
        }

        return self::matchesAny(self::providerSettings($provider)['patterns'] ?? [], $model);
    }

    /**
     * Determine whether the provider has retired the model.
     */
    public static function isRetired(string $model): bool
    {
        return self::matchesAny(config('ai-agent.image_models.retired', []), $model);
    }

    /**
     * Get the image model settings of the provider, or null when it has none.
     */
    protected static function providerSettings(string $provider): ?array
    {
        $settings = config("ai-agent.image_models.providers.{$provider}");

        return is_array($settings) && ! empty($settings['fallback']) ? $settings : null;
    }

    /**
     * Determine whether the model matches any of the patterns.
     */
    protected static function matchesAny(array $patterns, string $model): bool
    {
        return array_any($patterns, fn (string $pattern): bool => preg_match($pattern, $model) === 1);
    }
}
