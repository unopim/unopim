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
        '/voxtral/i',
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
        '/:ft-/i',

        '/-instruct$/i',
        '/(^|[-_])search([-_]|$)/i',

        '/^aqa$/i',
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
        '/(^|[-_])image([-_]|$)/i',
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
     * Return the models to auto-select after a fetch: the newest text models,
     * which serve chat, content generation and the AI agent, plus the newest
     * image model when the provider offers one, capped at AUTO_SELECT_LIMIT.
     *
     * Models are ordered by the release timestamp the provider's API reports,
     * falling back to the version in the model name for providers that report
     * none. Falls back to the unfiltered list when the category filter removes
     * everything, so an unusual provider still yields a selection.
     *
     * @param  string[]  $models
     * @param  array<string, int|null>  $released
     * @return string[]
     */
    public static function recommend(array $models, ?int $limit = null, array $released = []): array
    {
        $limit ??= self::AUTO_SELECT_LIMIT;

        if ($models === [] || $limit <= 0) {
            return [];
        }

        $candidates = array_map(strval(...), self::chatCapable($models));
        $imageModels = array_values(array_filter($candidates, self::isImageOnly(...)));
        $textModels = array_values(array_diff($candidates, $imageModels));

        if ($textModels === [] || $imageModels === []) {
            return array_slice(self::distinctFamilies(self::newestFirst($candidates, $released)), 0, $limit);
        }

        $picked = array_slice(self::distinctFamilies(self::newestFirst($textModels, $released)), 0, max($limit - 1, 1));

        if (count($picked) < $limit) {
            $picked[] = self::distinctFamilies(self::newestFirst($imageModels, $released))[0];
        }

        return $picked;
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
     * Order newest first: by the provider-reported release timestamp, then the
     * version in the model name, then the preference score, keeping the
     * provider's own order among otherwise equal models.
     *
     * @param  string[]  $models
     * @param  array<string, int|null>  $released
     * @return string[]
     */
    protected static function newestFirst(array $models, array $released): array
    {
        $ranked = array_map(
            static fn (int $position, string $model): array => [
                'model' => $model,
                'key'   => [$released[$model] ?? 0, self::version($model), self::score($model), -$position],
            ],
            array_keys($models),
            array_values($models)
        );

        usort($ranked, static fn (array $a, array $b): int => $b['key'] <=> $a['key']);

        return array_column($ranked, 'model');
    }

    /**
     * Keep one model per family, so a rolling alias and its dated snapshots
     * (mistral-small-latest, mistral-small-2603) take a single slot. The
     * -latest alias wins because it keeps tracking the provider's updates.
     *
     * @param  string[]  $models
     * @return string[]
     */
    protected static function distinctFamilies(array $models): array
    {
        $families = [];

        foreach ($models as $model) {
            $family = (string) preg_replace('/-(latest|\d{4})$/i', '', $model);

            if (! isset($families[$family]) || str_ends_with($model, '-latest')) {
                $families[$family] = $model;
            }
        }

        return array_values($families);
    }

    /**
     * The generation number in a model name (gpt-4.1 → 4.1, claude-sonnet-4-5
     * → 4.5, gemini-3.1-flash → 3.1). Four-digit and longer runs are dates or
     * snapshot stamps, and numbers ending in b or k are parameter counts or
     * context sizes (gpt-oss-20b, gpt-3.5-turbo-16k), not versions.
     */
    protected static function version(string $model): float
    {
        if (! preg_match('/(?<!\d)(\d{1,2})(?:[.\-](\d{1,2}))?(?![\dbk])/i', $model, $matches)) {
            return 0.0;
        }

        return (float) ($matches[1].'.'.($matches[2] ?? '0'));
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
        return self::textModels($models)[0] ?? null;
    }

    /**
     * The models safe to send a text prompt to, in the given order. When every
     * model looks image-only the first one is returned alone, so the caller
     * can still attempt the request and surface the provider's error.
     *
     * @param  string[]  $models
     * @return string[]
     */
    public static function textModels(array $models): array
    {
        if ($models === []) {
            return [];
        }

        $textModels = array_values(array_filter($models, static fn (string $model): bool => ! self::isImageOnly($model)));

        return $textModels ?: [$models[0]];
    }

    protected static function isImageOnly(string $model): bool
    {
        return array_any(self::IMAGE_ONLY_PATTERNS, fn (string $pattern): int|false => preg_match($pattern, $model));
    }
}
