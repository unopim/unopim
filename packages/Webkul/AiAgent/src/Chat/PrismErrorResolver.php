<?php

namespace Webkul\AiAgent\Chat;

use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Throwable;

/**
 * Maps Prism provider exceptions to user-friendly translated messages.
 *
 * Prism's built-in exception messages (e.g. "You hit a provider rate limit.
 * Details: []") are technical and surface raw JSON to end users. This resolver
 * converts them into actionable, localized messages for the chat UI and the
 * platform connection-test endpoint.
 */
class PrismErrorResolver
{
    /**
     * Resolve a thrown exception to a translated message and HTTP status code.
     *
     * @return array{message: string, status: int, is_known: bool}
     */
    public static function resolve(Throwable $e): array
    {
        if ($e instanceof PrismRateLimitedException) {
            $message = $e->retryAfter
                ? trans('ai-agent::app.common.error-rate-limit-retry', ['seconds' => $e->retryAfter])
                : trans('ai-agent::app.common.error-rate-limit');

            return [
                'message'  => $message,
                'status'   => 429,
                'is_known' => true,
            ];
        }

        if ($e instanceof PrismProviderOverloadedException) {
            return [
                'message'  => trans('ai-agent::app.common.error-overloaded'),
                'status'   => 503,
                'is_known' => true,
            ];
        }

        if ($e instanceof PrismRequestTooLargeException) {
            return [
                'message'  => trans('ai-agent::app.common.error-request-too-large'),
                'status'   => 413,
                'is_known' => true,
            ];
        }

        // Unknown exception: surface the actual provider/upstream message so
        // users can see what really went wrong (invalid API key, quota
        // exceeded, model not found, context length exceeded, etc.) instead
        // of a generic placeholder. Only fall back to the translated generic
        // error when the exception has no message at all.
        return [
            'message'  => self::sanitizeRawMessage($e) ?: trans('ai-agent::app.common.error-generic'),
            'status'   => 500,
            'is_known' => false,
        ];
    }

    /**
     * Clean up a raw exception message for display:
     *  - trim whitespace
     *  - collapse runs of whitespace
     *  - truncate to a reasonable length so multi-paragraph stack-trace-like
     *    messages don't flood the chat bubble
     *  - strip a trailing empty "Details: []" dump that Prism appends to some
     *    exceptions when the provider returned no structured rate-limit info
     */
    protected static function sanitizeRawMessage(Throwable $e): string
    {
        $message = trim((string) $e->getMessage());

        if ($message === '') {
            return '';
        }

        // Collapse whitespace runs so multi-line error bodies render cleanly
        // in a single chat bubble.
        $message = (string) preg_replace('/\s+/', ' ', $message);

        // Drop Prism's empty "Details: []" suffix when the upstream error
        // didn't include structured data — it's noise for end users.
        $message = (string) preg_replace('/\s*Details:\s*\[\s*\]\s*$/', '', $message);

        // Cap length. 500 chars is enough room for the useful part of a
        // provider error message without turning the chat into a wall of text.
        if (mb_strlen($message) > 500) {
            $message = mb_substr($message, 0, 497).'...';
        }

        return $message;
    }
}
