<?php

use Webkul\MagicAI\Support\ModelRecommender;

it('caps the auto-selected models at the limit', function () {
    $models = [
        'gpt-5.1', 'gpt-5-mini', 'gpt-5-nano', 'claude-haiku-4-5', 'claude-opus-5',
        'gemini-3-flash', 'gemini-3-pro', 'deepseek-v3-2', 'grok-4.5', 'kimi-k2-6',
    ];

    expect(ModelRecommender::recommend($models))->toHaveCount(ModelRecommender::AUTO_SELECT_LIMIT);
});

it('prefers cost-effective tiers over flagship and preview models', function () {
    $recommended = ModelRecommender::recommend([
        'gpt-5-pro',
        'claude-opus-5',
        'gemini-3-flash-preview',
        'gpt-5-mini',
        'claude-haiku-4-5',
        'gemini-3-flash',
    ]);

    expect(array_slice($recommended, 0, 3))->toBe(['gpt-5-mini', 'claude-haiku-4-5', 'gemini-3-flash']);
});
