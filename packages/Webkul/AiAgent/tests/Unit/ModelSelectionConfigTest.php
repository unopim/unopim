<?php

use Webkul\MagicAI\Support\ModelRecommender;

it('caps the auto-selected models at the limit', function () {
    $models = [
        'gpt-5.1', 'gpt-5-mini', 'gpt-5-nano', 'claude-haiku-4-5', 'claude-opus-5',
        'gemini-3-flash', 'gemini-3-pro', 'deepseek-v3-2', 'grok-4.5', 'kimi-k2-6',
    ];

    expect(ModelRecommender::recommend($models))->toHaveCount(ModelRecommender::AUTO_SELECT_LIMIT);
});

it('prefers the newest generation and the cost-effective tier within it', function () {
    $recommended = ModelRecommender::recommend([
        'gpt-3.5-turbo',
        'gpt-4o-mini',
        'gpt-5-pro',
        'gpt-4.1-nano',
        'gpt-5-mini',
    ], 3);

    expect($recommended)->toBe(['gpt-5-mini', 'gpt-5-pro', 'gpt-4.1-nano']);
});
