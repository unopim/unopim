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
        '/safeguard/i',

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
        // OpenAI / Azure
        '/dall-?e/i',
        '/(^|[-_])gpt-image/i',
        '/chatgpt-image/i',
        '/(^|[-_])sora([-_]|$)/i',

        // Google (Gemini / Vertex)
        '/imagen/i',
        '/(^|[-_])veo([-_]|$)/i',

        // Stability / Black Forest Labs / Midjourney / Playground
        '/stable-?diffusion/i',
        '/(^|[-_])flux([-_]|$)/i',
        '/midjourney/i',
        '/playground-v/i',

        // Ideogram / Recraft / Kling / Luma / Pika / Runway / Hunyuan / CogVideo
        '/(^|[-_])ideogram([-_]|$)/i',
        '/(^|[-_])recraft([-_]|$)/i',
        '/(^|[-_])kling([-_]|$)/i',
        '/(^|[-_])luma([-_]|$)/i',
        '/(^|[-_])pika([-_]|$)/i',
        '/(^|[-_])runway([-_]|$)/i',
        '/hunyuan-?video/i',
        '/(^|[-_])cogvideo/i',
        '/(^|[-_])wan-?\d/i',
        '/animate-?diff/i',

        // Generic "image" / "video" families (catch-all)
        '/(^|[-_])image-?\d/i',
        '/(^|[-_])video-?\d/i',
    ];

    /**
     * How many recommended models are pre-selected after a fetch.
     */
    public const AUTO_SELECT_LIMIT = 5;

    /**
     * Weighted patterns used to rank the recommendation list, so the handful of
     * models pre-selected on the form are the cheap, widely used tiers rather
     * than whatever sorts first alphabetically.
     *
     * @var array<string, int>
     */
    protected const RANK_PATTERNS = [
        '/(^|[-_.])(mini|nano|lite|small|flash|haiku|turbo|instant)([-_.]|$)/i'    => 4,
        '/^(gpt|claude|gemini|llama|mistral|deepseek|qwen|grok|command)/i'         => 2,
        '/(preview|experimental|(^|[-_.])exp([-_.]|$)|beta|alpha|(^|[-_.])rc\d)/i' => -4,
        '/(^|[-_.])(pro|max|opus|ultra|large|thinking|reasoning)([-_.]|$)/i'       => -2,
    ];

    /**
     * Return the models to auto-select after a fetch: the chat/image-capable
     * subset, ranked cheapest-and-most-common first and capped at
     * AUTO_SELECT_LIMIT. Falls back to the unfiltered list when the category
     * filter removes everything, so an unusual provider still yields a
     * selection.
     *
     * @param  string[]  $models
     * @return string[]
     */
    public static function recommend(array $models): array
    {
        if ($models === []) {
            return [];
        }

        return array_slice(self::rank(self::chatCapable($models)), 0, self::AUTO_SELECT_LIMIT);
    }

    /**
     * The chat- and image-capable subset of $models, in the provider's own
     * order. Returns the input untouched when every model is filtered out, so
     * an unusual provider still yields a usable list.
     *
     * @param  string[]  $models
     * @return string[]
     */
    public static function chatCapable(array $models): array
    {
        $capable = array_values(array_filter($models, static fn (string $model): bool => array_all(self::EXCLUDE_PATTERNS, fn (string $pattern): bool => ! preg_match($pattern, $model))));

        return $capable ?: array_values($models);
    }

    /**
     * Order by descending preference score, keeping the provider's own order
     * among equally scored models.
     *
     * @param  string[]  $models
     * @return string[]
     */
    protected static function rank(array $models): array
    {
        $ranked = array_map(
            static fn (int $position, $model): array => ['model' => (string) $model, 'position' => $position, 'score' => self::score((string) $model)],
            array_keys($models),
            array_values($models)
        );

        usort($ranked, static fn (array $a, array $b): int => [$b['score'], $a['position']] <=> [$a['score'], $b['position']]);

        return array_column($ranked, 'model');
    }

    protected static function score(string $model): int
    {
        $score = 0;

        foreach (self::RANK_PATTERNS as $pattern => $weight) {
            if (preg_match($pattern, $model)) {
                $score += $weight;
            }
        }

        return $score;
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
        if ($models === []) {
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
        return array_any(self::IMAGE_ONLY_PATTERNS, fn (string $pattern): int|false => preg_match($pattern, $model));
    }
}
