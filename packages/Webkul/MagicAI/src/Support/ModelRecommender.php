<?php

namespace Webkul\MagicAI\Support;

/**
 * Picks the subset of a provider's model catalogue that should be
 * auto-selected on the "Add AI Platform" form.
 *
 * Uses a provider-agnostic negative filter: we strip out model families that
 * aren't useful for chat or image generation (embeddings, speech, moderation,
 * legacy completion bases, etc.) and return everything else. This avoids any
 * hardcoded per-provider "best model" lists so it keeps working as providers
 * ship new models, and it includes image generation models (dall-e, imagen,
 * chatgpt-image-latest, etc.) that a chat-only pattern match would miss.
 */
class ModelRecommender
{
    /**
     * Patterns matched against the model ID (case-insensitive). A hit on any
     * pattern removes the model from the recommendation list.
     *
     * These are model *category* keywords — generic across providers — not
     * provider-specific model names.
     *
     * @var string[]
     */
    protected const EXCLUDE_PATTERNS = [
        // Embeddings — text, image, code
        '/embed/i',

        // Speech-to-text
        '/whisper/i',
        '/transcribe/i',

        // Text-to-speech
        '/(^|[-_])tts([-_]|$)/i',
        '/text-to-speech/i',

        // Content moderation / safety
        '/moderation/i',
        '/(^|[-_])guard([-_]|$)/i',

        // Realtime / audio API variants (different API surface — not usable
        // as regular chat completions)
        '/-realtime(-|$)/i',
        '/-audio(-|$)/i',

        // Computer-use / tool-specific preview families (niche)
        '/computer-use/i',

        // Code-completion / IDE-specific families (e.g. codex) — different
        // API surface than chat and usually org-restricted
        '/(^|[-_])codex([-_]|$)/i',

        // Search / ranking / retrieval API variants (different API surface)
        '/search-?api/i',
        '/(^|[-_])ranker([-_]|$)/i',

        // Deep-research variants — background research pipelines with a
        // different API surface than chat completions
        '/deep-research/i',

        // Dated model snapshots (e.g. gpt-4o-2024-05-13, o3-2025-04-16) —
        // we already auto-select the rolling alias, so snapshots are noise.
        '/-\d{4}-\d{2}-\d{2}$/',
        '/-\d{8}$/',

        // Legacy OpenAI GPT-3 completion bases (deprecated for chat use)
        '/^(ada|babbage|curie|davinci)(-\d+)?$/i',

        // Legacy "text-*" families (text-embedding, text-search, etc.)
        '/^text-(embedding|moderation|similarity|search|davinci-edit)/i',

        // User-fine-tuned models (org-specific, not generally reusable)
        '/^ft:/i',
    ];

    /**
     * Patterns identifying models that only support image/video generation,
     * not text completion. Used by {@see pickTextModel()} when the caller
     * needs a model guaranteed to accept a "Say OK" style prompt.
     *
     * @var string[]
     */
    protected const IMAGE_ONLY_PATTERNS = [
        '/dall-?e/i',
        '/imagen/i',
        '/(^|[-_])gpt-image/i',
        '/chatgpt-image/i',
        '/(^|[-_])image-?\d/i',
        '/(^|[-_])sora([-_]|$)/i',
        '/stable-?diffusion/i',
        '/(^|[-_])flux([-_]|$)/i',
        '/midjourney/i',
        '/playground-v/i',
    ];

    /**
     * Return the recommended subset of $models. Never returns an empty array
     * when $models is non-empty — if the filter removes everything (e.g. an
     * unusual provider), the original list is returned so the form stays
     * usable.
     *
     * @param  string[]  $models
     * @return string[]
     */
    public static function recommend(array $models): array
    {
        if (empty($models)) {
            return [];
        }

        $recommended = array_values(array_filter($models, static function ($model) {
            foreach (self::EXCLUDE_PATTERNS as $pattern) {
                if (preg_match($pattern, $model)) {
                    return false;
                }
            }

            return true;
        }));

        return $recommended ?: $models;
    }

    /**
     * Pick a model that is safe to send a text "ping" prompt to — i.e. not
     * an image- or video-generation model.
     *
     * Used by the platform connection-test endpoint, which sends a tiny text
     * completion ("Say OK") to verify the API key. Previously the test picked
     * the first model in the configured list, which could end up being an
     * image-only model like `chatgpt-image-latest`, making the provider
     * reject the request with "The requested model was not found" (400).
     *
     * Returns null when $models is empty. When every model looks image-only,
     * the first one is returned as a last-resort fallback so the caller can
     * still attempt the request and surface the provider's error.
     *
     * @param  string[]  $models
     */
    public static function pickTextModel(array $models): ?string
    {
        if (empty($models)) {
            return null;
        }

        foreach ($models as $model) {
            if (! self::isImageOnly($model)) {
                return $model;
            }
        }

        return $models[0];
    }

    protected static function isImageOnly(string $model): bool
    {
        foreach (self::IMAGE_ONLY_PATTERNS as $pattern) {
            if (preg_match($pattern, $model)) {
                return true;
            }
        }

        return false;
    }
}
