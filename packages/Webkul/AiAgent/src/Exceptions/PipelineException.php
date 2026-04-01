<?php

namespace Webkul\AiAgent\Exceptions;

use RuntimeException;

/**
 * Thrown when a pipeline stage encounters an unrecoverable error.
 */
class PipelineException extends RuntimeException
{
    /**
     * @param  string  $stage  The pipeline stage class that failed
     */
    public function __construct(
        string $message,
        public readonly string $stage = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
