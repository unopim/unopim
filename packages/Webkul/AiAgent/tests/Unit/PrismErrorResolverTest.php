<?php

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Webkul\AiAgent\Chat\PrismErrorResolver;

/**
 * Build a Laravel HTTP RequestException wrapping a fake upstream response.
 */
function makeRequestException(int $status, ?string $body, string $contentType = 'application/json'): RequestException
{
    $psr = new PsrResponse($status, ['Content-Type' => $contentType], $body ?? '');

    return new RequestException(new Response($psr));
}

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

it('extracts a Cerebras-style flat-JSON upstream body when Prism reports Unknown error', function () {
    // Cerebras puts the error message at the top level of the JSON body
    // (not nested under "error.message" like OpenAI), so Prism's Groq class
    // can't see it and falls back to the literal "Unknown error" placeholder.
    // The resolver must walk the previous chain and pull the body itself.
    $cerebrasBody = json_encode([
        'message' => 'Payment required to access this resource. Visit your billing tab.',
        'type'    => 'payment_required_error',
        'param'   => 'quota',
        'code'    => 'payment_required',
    ]);

    $requestException = makeRequestException(402, $cerebrasBody);

    $prismException = PrismException::providerRequestErrorWithDetails(
        provider: 'Groq',
        statusCode: 402,
        errorType: null,
        errorMessage: null,
        previous: $requestException
    );

    $resolved = PrismErrorResolver::resolve($prismException);

    expect($resolved['is_known'])->toBeFalse();
    expect($resolved['message'])->toBe(
        'HTTP 402: Payment required to access this resource. Visit your billing tab.'
    );
});

it('extracts a structured error.message field when present in the upstream body', function () {
    $body = json_encode([
        'error' => [
            'message' => 'You exceeded your current quota, please check your plan.',
            'type'    => 'insufficient_quota',
            'code'    => 'insufficient_quota',
        ],
    ]);

    $requestException = makeRequestException(429, $body);

    $prismException = PrismException::providerRequestErrorWithDetails(
        provider: 'OpenAI',
        statusCode: 429,
        errorType: null,
        errorMessage: null,
        previous: $requestException
    );

    $resolved = PrismErrorResolver::resolve($prismException);

    expect($resolved['message'])->toBe(
        'HTTP 429: You exceeded your current quota, please check your plan.'
    );
});

it('walks deeper than one level of previous chain to find the RequestException', function () {
    $requestException = makeRequestException(503, json_encode([
        'message' => 'Service temporarily unavailable',
    ]));

    $intermediate = new RuntimeException('intermediate wrapper', 0, $requestException);

    $prismException = PrismException::providerRequestErrorWithDetails(
        provider: 'Groq',
        statusCode: 503,
        errorType: null,
        errorMessage: null,
        previous: $intermediate
    );

    $resolved = PrismErrorResolver::resolve($prismException);

    expect($resolved['message'])->toBe('HTTP 503: Service temporarily unavailable');
});

it('falls back to a raw body slice when JSON has no recognized error field', function () {
    $body = json_encode([
        'foo' => 'bar',
        'baz' => 'qux',
    ]);

    $requestException = makeRequestException(500, $body);

    $prismException = PrismException::providerRequestErrorWithDetails(
        provider: 'Groq',
        statusCode: 500,
        errorType: null,
        errorMessage: null,
        previous: $requestException
    );

    $resolved = PrismErrorResolver::resolve($prismException);

    expect($resolved['message'])->toStartWith('HTTP 500: ');
    expect($resolved['message'])->toContain('foo');
    expect($resolved['message'])->toContain('bar');
});

it('returns the original Prism message when no RequestException is in the previous chain', function () {
    // No RequestException to walk to — the resolver must keep the original
    // Prism text rather than producing an empty string.
    $exception = new RuntimeException('Some real error message that is not Unknown');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['message'])->toBe('Some real error message that is not Unknown');
});

it('keeps the Prism placeholder when Unknown error has no upstream body to extract', function () {
    // Edge case: the message contains "Unknown error" but there's no
    // RequestException to walk to. Don't blank out the message — leave
    // whatever Prism gave us so the user at least sees something.
    $exception = new RuntimeException('Groq Error [402]: Unknown error');

    $resolved = PrismErrorResolver::resolve($exception);

    expect($resolved['message'])->toBe('Groq Error [402]: Unknown error');
});
