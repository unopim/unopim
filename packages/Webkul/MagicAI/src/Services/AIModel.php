<?php

namespace Webkul\MagicAI\Services;

use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

class AIModel
{
    /**
     * Gets the list of models from the default platform.
     */
    public static function getModels(): array
    {
        $platform = resolve(MagicAIPlatformRepository::class)->getActiveDefault();

        if (! $platform) {
            return [];
        }

        return array_map(fn ($model): array => [
            'id'    => $model,
            'label' => $model,
        ], $platform->model_list);
    }

    /**
     * Validate credentials by checking if a default platform exists.
     */
    public static function validate(): array
    {
        return self::getModels();
    }

    /**
     * Gets the available models from the default platform.
     */
    public static function getAvailableModels(): array
    {
        return self::getModels();
    }

    /**
     * Gets models for a specific platform by ID.
     */
    public static function getModelsForPlatform(int $platformId): array
    {
        return resolve(MagicAIPlatformRepository::class)->getModelOptions($platformId);
    }

    /**
     * Filter models to only image-capable ones based on provider.
     * Matches model names against the provider's known image-model naming patterns.
     */
    public static function filterImageModels(array $models, ?int $platformId = null): array
    {
        $repo = resolve(MagicAIPlatformRepository::class);
        $platform = $platformId ? $repo->find($platformId) : $repo->getActiveDefault();

        if (! $platform) {
            return [];
        }

        $provider = AiProvider::tryFrom($platform->provider);

        if (! $provider || ! $provider->supportsImages()) {
            return [];
        }

        // Image model patterns per provider - matches known image model naming conventions
        $imagePatterns = match ($provider) {
            AiProvider::OpenAI  => ['/dall-e/i', '/gpt-image/i', '/image/i'],
            AiProvider::Gemini  => ['/imagen/i', '/image/i'],
            AiProvider::XAI     => ['/image/i', '/grok.*vision/i'],
            default             => [],
        };

        if ($imagePatterns === []) {
            return $models;
        }

        $filtered = array_filter($models, function (string|array $model) use ($imagePatterns): bool {
            $id = is_array($model) ? ($model['id'] ?? '') : $model;

            return array_any($imagePatterns, fn (string $pattern): int|false => preg_match($pattern, $id));
        });

        return array_values($filtered);
    }
}
