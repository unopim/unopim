<?php

namespace Webkul\AiAgent\Exceptions;

use RuntimeException;

/**
 * Thrown when an AI provider API call fails.
 */
class AiApiException extends RuntimeException
{
    /**
     * @param  int  $statusCode  HTTP status code from the provider
     * @param  array<string, mixed>  $response  Decoded response body
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $response = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
