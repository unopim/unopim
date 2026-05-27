<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * Maps laravel/ai provider exceptions to user-friendly translated messages.
 */
class AiErrorResolver
{
    /**
     * Resolve a thrown exception to a translated message and HTTP status code.
     *
     * @return array{message: string, status: int, is_known: bool}
     */
    public static function resolve(Throwable $e): array
    {
        if (self::isDecryptException($e)) {
            return [
                'message'  => trans('ai-agent::app.common.error-api-key-corrupted', [
                    'error' => $e->getMessage(),
                ]),
                'status'   => 422,
                'is_known' => true,
            ];
        }

        if ($e instanceof RateLimitedException) {
            $retryAfter = method_exists($e, 'retryAfter') ? $e->retryAfter() : ($e->retryAfter ?? null);
            $message = $retryAfter
                ? trans('ai-agent::app.common.error-rate-limit-retry', ['seconds' => $retryAfter])
                : trans('ai-agent::app.common.error-rate-limit');

            return [
                'message'  => $message,
                'status'   => 429,
                'is_known' => true,
            ];
        }

        if ($e instanceof ProviderOverloadedException) {
            return [
                'message'  => trans('ai-agent::app.common.error-overloaded'),
                'status'   => 503,
                'is_known' => true,
            ];
        }

        // HTTP 413 (request too large) bubbles up as a Laravel RequestException
        // in laravel/ai 0.7 — no dedicated exception class. Detect via status.
        if (self::isRequestTooLarge($e)) {
            return [
                'message'  => trans('ai-agent::app.common.error-request-too-large'),
                'status'   => 413,
                'is_known' => true,
            ];
        }

        return [
            'message'  => self::sanitizeRawMessage($e) ?: trans('ai-agent::app.common.error-generic'),
            'status'   => 500,
            'is_known' => false,
        ];
    }

    protected static function sanitizeRawMessage(Throwable $e): string
    {
        $message = trim((string) $e->getMessage());

        if ($message === '' || str_contains($message, 'Unknown error')) {
            $upstream = self::extractUpstreamBody($e);
            if ($upstream !== '') {
                $message = $upstream;
            }
        }

        if ($message === '') {
            return '';
        }

        $message = (string) preg_replace('/\s+/', ' ', $message);
        $message = (string) preg_replace('/\s*Details:\s*\[\s*\]\s*$/', '', $message);

        if (mb_strlen($message) > 500) {
            $message = mb_substr($message, 0, 497).'...';
        }

        return $message;
    }

    protected static function extractUpstreamBody(Throwable $e): string
    {
        $current = $e;

        while ($current !== null) {
            if ($current instanceof RequestException && $current->response !== null) {
                $status = $current->response->status();
                $body = trim((string) $current->response->body());

                if ($body === '') {
                    return '';
                }

                $json = $current->response->json();
                if (is_array($json)) {
                    foreach (['error.message', 'error', 'message', 'detail', 'detail.message'] as $path) {
                        $candidate = data_get($json, $path);
                        if (is_string($candidate) && trim($candidate) !== '') {
                            return sprintf('HTTP %d: %s', $status, trim($candidate));
                        }
                    }
                }

                return sprintf('HTTP %d: %s', $status, mb_substr($body, 0, 400));
            }

            $current = $current->getPrevious();
        }

        return '';
    }

    protected static function isDecryptException(Throwable $e): bool
    {
        $current = $e;

        while ($current !== null) {
            if ($current instanceof DecryptException) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }

    protected static function isRequestTooLarge(Throwable $e): bool
    {
        $current = $e;

        while ($current !== null) {
            if ($current instanceof RequestException && $current->response !== null
                && $current->response->status() === 413) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }
}
