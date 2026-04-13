<?php

use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Webkul\AiAgent\Chat\PrismErrorResolver;

it('resolves rate-limit exception to friendly message without surfacing raw details', function () {
    $exception = PrismRateLimitedException::make(rateLimits: []);

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['status'])->toBe(429);
    expect($resolved['is_known'])->toBeTrue();
    expect($resolved['message'])->not->toContain('Details: []');
    expect($resolved['message'])->not->toContain('You hit a provider rate limit');
    expect($resolved['message'])->toBe(trans('ai-agent::app.common.error-rate-limit'));
});

it('includes retry-after seconds when provided by the exception', function () {
    $exception = PrismRateLimitedException::make(rateLimits: [], retryAfter: 30);

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['status'])->toBe(429);
    expect($resolved['message'])->toBe(
        trans('ai-agent::app.common.error-rate-limit-retry', ['seconds' => 30])
    );
    expect($resolved['message'])->toContain('30');
});

it('resolves provider overloaded exception to a friendly message', function () {
    $exception = PrismProviderOverloadedException::make('openai');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['status'])->toBe(503);
    expect($resolved['is_known'])->toBeTrue();
    expect($resolved['message'])->toBe(trans('ai-agent::app.common.error-overloaded'));
});

it('resolves request-too-large exception to a friendly message', function () {
    $exception = new PrismRequestTooLargeException('request too large');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['status'])->toBe(413);
    expect($resolved['is_known'])->toBeTrue();
    expect($resolved['message'])->toBe(trans('ai-agent::app.common.error-request-too-large'));
});

it('surfaces the underlying provider message for unknown exceptions', function () {
    // For unknown errors we want the real upstream message (e.g. "Invalid
    // API key", "Quota exceeded", "Model not found") to reach the user
    // instead of a static placeholder — that's how they know what to fix.
    $exception = new RuntimeException('Incorrect API key provided: sk-****');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['status'])->toBe(500);
    expect($resolved['is_known'])->toBeFalse();
    expect($resolved['message'])->toBe('Incorrect API key provided: sk-****');
});

it('falls back to the generic translated message when the exception has no message', function () {
    $exception = new RuntimeException('');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['is_known'])->toBeFalse();
    expect($resolved['message'])->toBe(trans('ai-agent::app.common.error-generic'));
});

it('truncates overly long unknown exception messages', function () {
    $long = str_repeat('X', 800);
    $exception = new RuntimeException($long);

    $resolved = PrismErrorResolver::resolve($exception);

    expect(mb_strlen($resolved['message']))->toBe(500);
    expect($resolved['message'])->toEndWith('...');
});

it('strips Prism empty Details: [] suffix from unknown exception messages', function () {
    $exception = new RuntimeException('Quota exceeded. Details: []');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['message'])->toBe('Quota exceeded.');
});

it('collapses whitespace in multiline unknown exception messages', function () {
    $exception = new RuntimeException("Something\n\nbad\n   happened");

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['message'])->toBe('Something bad happened');
});
