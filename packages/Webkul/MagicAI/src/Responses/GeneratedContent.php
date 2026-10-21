<?php

declare(strict_types=1);

namespace Webkul\MagicAI\Responses;

/**
 * A completed generation together with whether the model stopped because it
 * ran out of tokens rather than because it had finished.
 */
final readonly class GeneratedContent
{
    public function __construct(
        public string $text,
        public bool $truncated = false,
    ) {}
}
