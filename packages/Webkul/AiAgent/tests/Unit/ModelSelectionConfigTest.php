<?php

use Webkul\MagicAI\Support\ModelRecommender;

it('caps the auto-selected models at the configured limit', function () {
    config(['magic_ai.models.auto_select_limit' => 5]);

    $models = [
        'gpt-5.1', 'gpt-5-mini', 'gpt-5-nano', 'claude-haiku-4-5', 'claude-opus-5',
        'gemini-3-flash', 'gemini-3-pro', 'deepseek-v3-2', 'grok-4.5', 'kimi-k2-6',
    ];

    expect(ModelRecommender::recommend($models))->toHaveCount(5);
});

it('honours a limit of zero by selecting nothing', function () {
    config(['magic_ai.models.auto_select_limit' => 0]);

    expect(ModelRecommender::recommend(['gpt-5.1', 'gpt-5-mini']))->toBe([]);
});

it('prefers the newest generation and the cost-effective tier within it', function () {
    config(['magic_ai.models.auto_select_limit' => 3]);

    $recommended = ModelRecommender::recommend([
        'gpt-3.5-turbo',
        'gpt-4o-mini',
        'gpt-5-pro',
        'gpt-4.1-nano',
        'gpt-5-mini',
    ]);

    expect($recommended)->toBe(['gpt-5-mini', 'gpt-5-pro', 'gpt-4.1-nano']);
});

it('returns every fetched model when no allow list is configured', function () {
    config(['magic_ai.models.allowed' => []]);

    $models = ['gpt-5.1', 'claude-opus-5', 'gemini-3-pro'];

    expect(ModelRecommender::allowed($models))->toBe($models);
});

it('intersects fetched models with the configured allow list, preserving fetch order', function () {
    config(['magic_ai.models.allowed' => ['claude-opus-5', 'gpt-5.1', 'not-offered-by-provider']]);

    expect(ModelRecommender::allowed(['gpt-5.1', 'claude-opus-5', 'gemini-3-pro']))
        ->toBe(['gpt-5.1', 'claude-opus-5']);
});

it('matches allow-list entries regardless of case and surrounding spaces', function () {
    config(['magic_ai.models.allowed' => ['  GPT-5.1  ']]);

    expect(ModelRecommender::allowed(['gpt-5.1', 'gemini-3-pro']))->toBe(['gpt-5.1']);
});
