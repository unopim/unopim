<?php

use Webkul\MagicAI\Support\ModelRecommender;

it('returns an empty list when no models are given', function () {
    expect(ModelRecommender::recommend([]))->toBe([]);
});

it('excludes embedding models from the recommendation', function () {
    $models = [
        'gpt-4o',
        'text-embedding-3-small',
        'text-embedding-ada-002',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toBe(['gpt-4o']);
});

it('excludes whisper / audio / tts / moderation / computer-use / guard models', function () {
    $models = [
        'gpt-4o',
        'whisper-1',
        'gpt-4o-audio-preview',
        'gpt-4o-realtime-preview',
        'tts-1',
        'tts-1-hd',
        'omni-moderation-latest',
        'text-moderation-latest',
        'computer-use-preview',
        'computer-use-preview-2025-03-11',
        'llama-guard-3-8b',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toBe(['gpt-4o']);
});

it('excludes legacy GPT-3 completion bases but keeps chat models', function () {
    $models = [
        'ada',
        'babbage',
        'curie',
        'davinci',
        'babbage-002',
        'davinci-002',
        'gpt-3.5-turbo',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toBe(['gpt-3.5-turbo']);
});

it('includes image generation models (dall-e, chatgpt-image-latest, imagen)', function () {
    $models = [
        'gpt-4o',
        'dall-e-2',
        'dall-e-3',
        'chatgpt-image-latest',
        'imagen-3.0-generate-002',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toContain('gpt-4o');
    expect($recommended)->toContain('dall-e-2');
    expect($recommended)->toContain('dall-e-3');
    expect($recommended)->toContain('chatgpt-image-latest');
    expect($recommended)->toContain('imagen-3.0-generate-002');
});

it('works with a realistic OpenAI catalogue and keeps both chat and image models', function () {
    // Snapshot of a typical OpenAI /v1/models response
    $models = [
        'babbage-002',
        'chatgpt-image-latest',
        'computer-use-preview',
        'dall-e-2',
        'dall-e-3',
        'davinci-002',
        'gpt-3.5-turbo',
        'gpt-3.5-turbo-instruct',
        'gpt-4',
        'gpt-4-turbo',
        'gpt-4o',
        'gpt-4o-audio-preview',
        'gpt-4o-mini',
        'gpt-4o-mini-realtime-preview',
        'gpt-4o-realtime-preview',
        'omni-moderation-latest',
        'text-embedding-3-large',
        'text-embedding-3-small',
        'text-embedding-ada-002',
        'tts-1',
        'tts-1-hd',
        'whisper-1',
    ];

    $recommended = ModelRecommender::recommend($models);

    // Chat models kept
    expect($recommended)->toContain('gpt-3.5-turbo');
    expect($recommended)->toContain('gpt-3.5-turbo-instruct');
    expect($recommended)->toContain('gpt-4');
    expect($recommended)->toContain('gpt-4-turbo');
    expect($recommended)->toContain('gpt-4o');
    expect($recommended)->toContain('gpt-4o-mini');

    // Image generation models kept
    expect($recommended)->toContain('chatgpt-image-latest');
    expect($recommended)->toContain('dall-e-2');
    expect($recommended)->toContain('dall-e-3');

    // Non-chat / non-image types filtered out
    expect($recommended)->not->toContain('babbage-002');
    expect($recommended)->not->toContain('computer-use-preview');
    expect($recommended)->not->toContain('davinci-002');
    expect($recommended)->not->toContain('gpt-4o-audio-preview');
    expect($recommended)->not->toContain('gpt-4o-mini-realtime-preview');
    expect($recommended)->not->toContain('gpt-4o-realtime-preview');
    expect($recommended)->not->toContain('omni-moderation-latest');
    expect($recommended)->not->toContain('text-embedding-3-large');
    expect($recommended)->not->toContain('text-embedding-3-small');
    expect($recommended)->not->toContain('text-embedding-ada-002');
    expect($recommended)->not->toContain('tts-1');
    expect($recommended)->not->toContain('tts-1-hd');
    expect($recommended)->not->toContain('whisper-1');
});

it('excludes user fine-tuned models', function () {
    $models = [
        'gpt-4o',
        'ft:gpt-3.5-turbo-0125:my-org::abc123',
    ];

    expect(ModelRecommender::recommend($models))->toBe(['gpt-4o']);
});

it('keeps models from non-OpenAI providers without hardcoded knowledge', function () {
    // Anthropic / Gemini / Groq / xAI-style names — no provider-specific
    // pattern should be needed to keep any of these.
    $models = [
        'claude-sonnet-4-5',
        'claude-opus-4-1',
        'claude-haiku-4-5',
        'gemini-2.0-flash',
        'gemini-2.0-pro',
        'imagen-3.0-generate-002',
        'llama-3.3-70b-versatile',
        'mixtral-8x7b-32768',
        'grok-2',
        'grok-2-vision',
    ];

    $recommended = ModelRecommender::recommend($models);

    // All of them should be kept — none match an exclusion pattern.
    expect($recommended)->toEqualCanonicalizing($models);
});

it('falls back to the full list when every model matches an exclusion pattern', function () {
    // Unusual edge case: a provider that only exposes embeddings. We still
    // return the list so the form is usable rather than showing empty state.
    $models = ['text-embedding-3-small', 'text-embedding-ada-002'];

    expect(ModelRecommender::recommend($models))->toBe($models);
});

it('excludes dated model snapshots but keeps the rolling alias', function () {
    $models = [
        'gpt-4o',
        'gpt-4o-2024-05-13',
        'gpt-4o-2024-08-06',
        'o1',
        'o1-2024-12-17',
        'claude-3-5-sonnet-20241022',
        'o3-2025-04-16',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toContain('gpt-4o');
    expect($recommended)->toContain('o1');
    expect($recommended)->not->toContain('gpt-4o-2024-05-13');
    expect($recommended)->not->toContain('gpt-4o-2024-08-06');
    expect($recommended)->not->toContain('o1-2024-12-17');
    expect($recommended)->not->toContain('claude-3-5-sonnet-20241022');
    expect($recommended)->not->toContain('o3-2025-04-16');
});

it('excludes codex, search-api, and deep-research variants', function () {
    $models = [
        'gpt-5',
        'gpt-5-codex',
        'gpt-5.1-codex-max',
        'gpt-5.1-codex-mini',
        'gpt-5-search-api',
        'o3',
        'o3-deep-research',
        'o4-mini-deep-research',
    ];

    $recommended = ModelRecommender::recommend($models);

    expect($recommended)->toContain('gpt-5');
    expect($recommended)->toContain('o3');
    expect($recommended)->not->toContain('gpt-5-codex');
    expect($recommended)->not->toContain('gpt-5.1-codex-max');
    expect($recommended)->not->toContain('gpt-5.1-codex-mini');
    expect($recommended)->not->toContain('gpt-5-search-api');
    expect($recommended)->not->toContain('o3-deep-research');
    expect($recommended)->not->toContain('o4-mini-deep-research');
});

it('produces a clean list for the realistic OpenAI catalogue from the bug report', function () {
    // Exact model list from the user's screenshot — the previous filter
    // over-selected snapshots, codex, search-api, and deep-research variants.
    $models = [
        'gpt-5-chat-latest',
        'gpt-5-codex',
        'gpt-5-mini',
        'gpt-5-nano',
        'gpt-5-nano-2025-08-07',
        'gpt-5-pro',
        'gpt-5-pro-2025-10-06',
        'gpt-5-search-api',
        'gpt-5-search-api-2025-10-14',
        'gpt-5.1',
        'gpt-5.1-2025-11-13',
        'gpt-5.1-chat-latest',
        'gpt-5.1-codex',
        'gpt-5.1-codex-max',
        'gpt-5.1-codex-mini',
        'gpt-5.2',
        'gpt-5.2-2025-12-11',
        'gpt-5.2-chat-latest',
        'gpt-5.2-codex',
        'gpt-5.2-pro',
        'gpt-5.2-pro-2025-12-11',
        'gpt-5.3-chat-latest',
        'gpt-5.3-codex',
        'gpt-5.4',
        'gpt-5.4-2026-03-05',
        'gpt-5.4-mini',
        'gpt-5.4-mini-2026-03-17',
        'gpt-5.4-nano',
        'gpt-5.4-nano-2026-03-17',
        'gpt-5.4-pro',
        'gpt-5.4-pro-2026-03-05',
        'gpt-image-1',
        'gpt-image-1-mini',
        'gpt-image-1.5',
        'o1',
        'o1-2024-12-17',
        'o1-pro',
        'o1-pro-2025-03-19',
        'o3',
        'o3-2025-04-16',
        'o3-deep-research',
        'o3-deep-research-2025-06-26',
        'o3-mini',
        'o3-mini-2025-01-31',
        'o3-pro',
        'o3-pro-2025-06-10',
        'o4-mini',
        'o4-mini-2025-04-16',
        'o4-mini-deep-research',
        'o4-mini-deep-research-2025-06-26',
        'sora-2',
        'sora-2-pro',
    ];

    $recommended = ModelRecommender::recommend($models);

    // Kept: rolling aliases + sensible variants
    $expectedKeeps = [
        'gpt-5-chat-latest', 'gpt-5-mini', 'gpt-5-nano', 'gpt-5-pro',
        'gpt-5.1', 'gpt-5.1-chat-latest', 'gpt-5.2', 'gpt-5.2-chat-latest',
        'gpt-5.2-pro', 'gpt-5.3-chat-latest', 'gpt-5.4', 'gpt-5.4-mini',
        'gpt-5.4-nano', 'gpt-5.4-pro', 'gpt-image-1', 'gpt-image-1-mini',
        'gpt-image-1.5', 'o1', 'o1-pro', 'o3', 'o3-mini', 'o3-pro',
        'o4-mini', 'sora-2', 'sora-2-pro',
    ];
    foreach ($expectedKeeps as $keep) {
        expect($recommended)->toContain($keep);
    }

    // Dropped: dated snapshots, codex, search-api, deep-research
    $expectedDrops = [
        'gpt-5-codex', 'gpt-5-nano-2025-08-07', 'gpt-5-pro-2025-10-06',
        'gpt-5-search-api', 'gpt-5-search-api-2025-10-14',
        'gpt-5.1-2025-11-13', 'gpt-5.1-codex', 'gpt-5.1-codex-max',
        'gpt-5.1-codex-mini', 'gpt-5.2-2025-12-11', 'gpt-5.2-codex',
        'gpt-5.2-pro-2025-12-11', 'gpt-5.3-codex', 'gpt-5.4-2026-03-05',
        'gpt-5.4-mini-2026-03-17', 'gpt-5.4-nano-2026-03-17',
        'gpt-5.4-pro-2026-03-05', 'o1-2024-12-17', 'o1-pro-2025-03-19',
        'o3-2025-04-16', 'o3-deep-research', 'o3-deep-research-2025-06-26',
        'o3-mini-2025-01-31', 'o3-pro-2025-06-10', 'o4-mini-2025-04-16',
        'o4-mini-deep-research', 'o4-mini-deep-research-2025-06-26',
    ];
    foreach ($expectedDrops as $drop) {
        expect($recommended)->not->toContain($drop);
    }
});

it('picks a text-capable model and skips image-only models', function () {
    // chatgpt-image-latest sorts first alphabetically — the bug repro
    $models = [
        'chatgpt-image-latest',
        'dall-e-2',
        'dall-e-3',
        'gpt-3.5-turbo',
        'gpt-4o',
    ];

    expect(ModelRecommender::pickTextModel($models))->toBe('gpt-3.5-turbo');
});

it('pickTextModel returns the first entry when all models look image-only', function () {
    $models = ['dall-e-2', 'dall-e-3', 'imagen-3.0-generate-002'];

    expect(ModelRecommender::pickTextModel($models))->toBe('dall-e-2');
});

it('pickTextModel returns null for an empty list', function () {
    expect(ModelRecommender::pickTextModel([]))->toBeNull();
});

it('pickTextModel recognises sora and stable-diffusion as image/video models', function () {
    $models = ['sora-2', 'stable-diffusion-xl', 'claude-sonnet-4-5', 'flux-dev'];

    expect(ModelRecommender::pickTextModel($models))->toBe('claude-sonnet-4-5');
});

it('pickTextModel skips Gemini image and video models and lands on a Gemini text model', function () {
    $models = ['imagen-3.0', 'veo-2', 'gemini-1.5-pro', 'gemini-1.5-flash'];

    expect(ModelRecommender::pickTextModel($models))->toBe('gemini-1.5-pro');
});

it('pickTextModel skips Ideogram, Recraft, Kling, Luma, Pika image/video models across providers', function () {
    $models = [
        'ideogram-v2',
        'recraft-v3',
        'kling-v1',
        'luma-dream-machine',
        'pika-1.0',
        'runway-gen-3',
        'llama-3.1-70b-instruct',
    ];

    expect(ModelRecommender::pickTextModel($models))->toBe('llama-3.1-70b-instruct');
});
